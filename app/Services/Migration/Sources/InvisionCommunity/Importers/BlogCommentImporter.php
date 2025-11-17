<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlogCommentImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'blog_comments';

    public const string CACHE_KEY_PREFIX = 'migration:ic:blog_comment_map:';

    public const string CACHE_TAG = 'migration:ic:blog_comments';

    public static function getCommentMapping(int $sourceCommentId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceCommentId);
    }

    public function isCompleted(): bool
    {
        return (bool) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.'completed');
    }

    public function markCompleted(): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.'completed', true, self::CACHE_TTL);
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

    /**
     * @return ImporterDependency[]
     */
    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Blog comments require users to exist for author assignment'),
            ImporterDependency::requiredPre('blogs', 'Blog comments require blog posts to exist'),
        ];
    }

    public function getTotalRecordsCount(): int
    {
        return $this->getBaseQuery()->count();
    }

    public function import(
        MigrationResult $result,
        OutputStyle $output,
        Factory $components,
    ): int {
        $config = $this->getConfig();

        $baseQuery = $this->getBaseQuery()
            ->when($config->offset !== null && $config->offset !== 0, fn ($builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn ($builder) => $builder->limit($config->limit));

        $totalComments = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s blog comments to migrate...', $totalComments));
        }

        $progressBar = $output->createProgressBar($totalComments);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($comments) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($comments as $sourceComment) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importComment($sourceComment, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceComment->comment_id ?? 'unknown',
                            'entry_id' => $sourceComment->comment_entry_id ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import blog comment', [
                        'source_id' => $sourceComment->comment_id ?? 'unknown',
                        'entry_id' => $sourceComment->comment_entry_id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import blog comment: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine(2);

        return $processed;
    }

    protected function importComment(object $sourceComment, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $blogPostId = BlogImporter::getBlogMapping($sourceComment->comment_entry_id);

        if ($blogPostId === null || $blogPostId === 0) {
            $result->incrementFailed(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordFailed(self::ENTITY_NAME, [
                    'source_id' => $sourceComment->comment_id,
                    'entry_id' => $sourceComment->comment_entry_id,
                    'error' => 'Blog post not found in mapping',
                ]);
            }

            return;
        }

        $author = $this->findAuthor($sourceComment);

        $comment = new Comment;
        $comment->forceFill([
            'commentable_type' => Post::class,
            'commentable_id' => $blogPostId,
            'content' => $sourceComment->comment_text,
            'is_approved' => (bool) $sourceComment->comment_approved,
            'parent_id' => null,
            'created_by' => $author instanceof User
                ? $author->id
                : null,
            'created_at' => Carbon::createFromTimestamp($sourceComment->comment_date),
            'updated_at' => $sourceComment->comment_edit_time
                ? Carbon::createFromTimestamp($sourceComment->comment_edit_time)
                : Carbon::createFromTimestamp($sourceComment->comment_date),
        ]);

        if (! $config->isDryRun) {
            $comment->save();
            $this->cacheCommentMapping($sourceComment->comment_id, $comment->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceComment->comment_id,
                'target_id' => $comment->id ?? 'N/A (dry run)',
                'entry_id' => $sourceComment->comment_entry_id,
                'blog_post_id' => $blogPostId,
                'author' => $author instanceof User
                    ? $author->name
                    : 'Guest',
                'created_at' => $comment->created_at?->toDateTimeString() ?? 'N/A',
            ]);
        }
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

    protected function cacheCommentMapping(int $sourceCommentId, int $targetCommentId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceCommentId, $targetCommentId, self::CACHE_TTL);
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();
        $config = $this->getConfig();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('comment_approved', 1)
            ->orderBy('comment_id')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('comment_member_id', $config->userId));
    }
}
