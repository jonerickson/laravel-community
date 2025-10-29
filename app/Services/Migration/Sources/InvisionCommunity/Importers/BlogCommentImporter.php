<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlogCommentImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'blog_comments';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:blog_comment_map:';

    protected const string CACHE_TAG = 'migration:ic:blog_comments';

    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    public static function getCommentMapping(int $sourceCommentId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceCommentId);
    }

    public function cleanup(): void
    {
        Cache::tags(self::CACHE_TAG)->flush();
    }

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getSourceTable(): string
    {
        return 'blog_comments';
    }

    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Blog comments require users to exist for author assignment'),
            ImporterDependency::requiredPre('blogs', 'Blog comments require blog posts to exist'),
        ];
    }

    public function import(
        string $connection,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        DB::connection($connection)->disableQueryLog();

        $query = DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('comment_approved', 1)
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->skip($offset));

        $totalComments = $limit !== null && $limit !== 0 ? min($limit, $query->count()) : $query->count();

        $output->writeln("Found {$totalComments} blog comments to migrate...");

        $progressBar = $output->createProgressBar($totalComments);
        $progressBar->start();

        $processed = 0;

        $query
            ->lazyById($batchSize, 'comment_id')
            ->each(function ($sourceComment) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): void {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return;
                }

                try {
                    $this->importComment($sourceComment, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourceComment->comment_id ?? 'unknown',
                        'entry_id' => $sourceComment->comment_entry_id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import blog comment', [
                        'source_id' => $sourceComment->comment_id ?? 'unknown',
                        'entry_id' => $sourceComment->comment_entry_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import blog comment: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importComment(object $sourceComment, bool $isDryRun, MigrationResult $result): void
    {
        $existingComment = Comment::query()
            ->where('commentable_type', Post::class)
            ->where('commentable_id', BlogImporter::getBlogMapping($sourceComment->comment_entry_id))
            ->where('created_at', Carbon::createFromTimestamp($sourceComment->comment_date))
            ->first();

        if ($existingComment) {
            $this->cacheCommentMapping($sourceComment->comment_id, $existingComment->id);
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceComment->comment_id,
                'entry_id' => $sourceComment->comment_entry_id,
                'reason' => 'Already exists',
            ]);

            return;
        }

        $blogPostId = BlogImporter::getBlogMapping($sourceComment->comment_entry_id);

        if ($blogPostId === null || $blogPostId === 0) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourceComment->comment_id,
                'entry_id' => $sourceComment->comment_entry_id,
                'error' => 'Blog post not found in mapping',
            ]);

            return;
        }

        $author = $this->findAuthor($sourceComment);

        if (! $author instanceof User) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourceComment->comment_id,
                'entry_id' => $sourceComment->comment_entry_id,
                'error' => 'Could not find author',
            ]);

            return;
        }

        $content = $this->cleanHtml($sourceComment->comment_text ?? '');

        $comment = new Comment;
        $comment->forceFill([
            'commentable_type' => Post::class,
            'commentable_id' => $blogPostId,
            'content' => $content,
            'is_approved' => (bool) $sourceComment->comment_approved,
            'parent_id' => null,
            'created_by' => $author->id,
            'created_at' => Carbon::createFromTimestamp($sourceComment->comment_date),
            'updated_at' => $sourceComment->comment_edit_time
                ? Carbon::createFromTimestamp($sourceComment->comment_edit_time)
                : Carbon::createFromTimestamp($sourceComment->comment_date),
        ]);

        if (! $isDryRun) {
            $comment->save();
            $this->cacheCommentMapping($sourceComment->comment_id, $comment->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourceComment->comment_id,
            'target_id' => $comment->id ?? 'N/A (dry run)',
            'entry_id' => $sourceComment->comment_entry_id,
            'blog_post_id' => $blogPostId,
            'author' => $author->name,
            'created_at' => $comment->created_at?->toDateTimeString() ?? 'N/A',
        ]);
    }

    protected function findAuthor(object $sourceComment): ?User
    {
        if (! $sourceComment->comment_member_id) {
            return null;
        }

        $mappedUserId = UserImporter::getUserMapping((int) $sourceComment->comment_member_id);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        return null;
    }

    protected function cleanHtml(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        return $html;
    }

    protected function cacheCommentMapping(int $sourceCommentId, int $targetCommentId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceCommentId, $targetCommentId, self::CACHE_TTL);
    }
}
