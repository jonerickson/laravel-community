<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\PostType;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunityLanguageResolver;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'posts';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:post_map:';

    protected const string CACHE_TAG = 'migration:ic:posts';

    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    public function __construct(
        protected ?InvisionCommunityLanguageResolver $languageResolver = null,
    ) {}

    public static function getPostMapping(int $sourcePostId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourcePostId);
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
        return 'forums_posts';
    }

    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Posts require users to exist for author assignment'),
            ImporterDependency::requiredPre('topics', 'Posts require topics to exist for proper assignment'),
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

        if (! $this->languageResolver instanceof InvisionCommunityLanguageResolver) {
            $this->languageResolver = new InvisionCommunityLanguageResolver($connection);
        }

        $query = DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('queued', 0)
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->skip($offset));

        $totalPosts = $limit !== null && $limit !== 0 ? min($limit, $query->count()) : $query->count();

        $output->writeln("Found {$totalPosts} posts to migrate...");

        $progressBar = $output->createProgressBar($totalPosts);
        $progressBar->start();

        $processed = 0;

        $query
            ->lazyById($batchSize, 'pid')
            ->each(function ($sourcePost) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): void {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return;
                }

                try {
                    $this->importPost($sourcePost, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourcePost->pid ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import post', [
                        'source_id' => $sourcePost->pid ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import post: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importPost(object $sourcePost, bool $isDryRun, MigrationResult $result): void
    {
        $content = $sourcePost->post ?? '';

        $authorId = UserImporter::getUserMapping((int) $sourcePost->author_id);
        $existingPost = Post::query()
            ->where('type', PostType::Forum)
            ->where('content', $content)
            ->when($authorId, fn (Builder $query) => $query->where('id', $authorId))
            ->first();

        if ($existingPost) {
            $this->cachePostMapping($sourcePost->pid, $existingPost->id);
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourcePost->pid,
                'reason' => 'Already exists',
            ]);

            return;
        }

        $author = $this->findOrCreateAuthor($sourcePost);

        if (! $author instanceof User) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourcePost->pid,
                'error' => 'Could not find or create author',
            ]);

            return;
        }

        $topic = $this->findTopic($sourcePost);

        if (! $topic instanceof Topic) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourcePost->pid,
                'error' => 'Could not find topic',
            ]);

            return;
        }

        $post = new Post;
        $post->forceFill([
            'type' => PostType::Forum,
            'topic_id' => $topic->id,
            'title' => "Re: $topic->title",
            'content' => $content,
            'is_published' => true,
            'is_approved' => true,
            'comments_enabled' => false,
            'created_by' => $author->id,
            'created_at' => $sourcePost->post_date
                ? Carbon::createFromTimestamp($sourcePost->post_date)
                : Carbon::now(),
            'updated_at' => $sourcePost->edit_time
                ? Carbon::createFromTimestamp($sourcePost->edit_time)
                : ($sourcePost->post_date
                    ? Carbon::createFromTimestamp($sourcePost->post_date)
                    : Carbon::now()),
        ]);

        if (! $isDryRun) {
            $post->save();
            $this->cachePostMapping($sourcePost->pid, $post->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourcePost->pid,
            'target_id' => $post->id ?? 'N/A (dry run)',
            'topic' => $topic->title,
            'author' => $author->name,
        ]);
    }

    protected function findOrCreateAuthor(object $sourcePost): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourcePost->author_id);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        return null;
    }

    protected function findTopic(object $sourcePost): ?Topic
    {
        $mappedTopicId = TopicImporter::getTopicMapping((int) $sourcePost->topic_id);

        if ($mappedTopicId !== null && $mappedTopicId !== 0) {
            return Topic::query()->find($mappedTopicId);
        }

        return null;
    }

    protected function cachePostMapping(int $sourcePostId, int $targetPostId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourcePostId, $targetPostId, self::CACHE_TTL);
    }
}
