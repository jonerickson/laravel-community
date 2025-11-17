<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\PostType;
use App\Models\Post;
use App\Models\Topic;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'posts';

    public const string CACHE_KEY_PREFIX = 'migration:ic:post_map:';

    public const string CACHE_TAG = 'migration:ic:posts';

    public static function getPostMapping(int $sourcePostId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourcePostId);
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
        return 'forums_posts';
    }

    /**
     * @return ImporterDependency[]
     */
    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Posts require users to exist for author assignment'),
            ImporterDependency::requiredPre('topics', 'Posts require topics to exist for proper assignment'),
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

        $totalPosts = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s posts to migrate...', $totalPosts));
        }

        $progressBar = $output->createProgressBar($totalPosts);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($posts) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($posts as $sourcePost) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importPost($sourcePost, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourcePost->pid ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import post', [
                        'source_id' => $sourcePost->pid ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import post: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
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

    protected function importPost(object $sourcePost, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $topic = $this->findTopic($sourcePost);

        if (! $topic instanceof Topic) {
            $result->incrementFailed(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordFailed(self::ENTITY_NAME, [
                    'source_id' => $sourcePost->pid,
                    'error' => 'Could not find topic',
                ]);
            }

            return;
        }

        $author = $this->findAuthor($sourcePost);

        $post = new Post;
        $post->forceFill([
            'type' => PostType::Forum,
            'topic_id' => $topic->id,
            'title' => Str::of('Re: '.$topic->title)->trim()->limit(255, '')->toString(),
            'content' => $this->modifyContent($sourcePost->post ?? '', $config),
            'is_published' => true,
            'is_approved' => true,
            'comments_enabled' => false,
            'created_by' => $author instanceof User
                ? $author->id
                : null,
            'created_at' => $sourcePost->post_date
                ? Carbon::createFromTimestamp($sourcePost->post_date)
                : Carbon::now(),
            'updated_at' => $sourcePost->edit_time
                ? Carbon::createFromTimestamp($sourcePost->edit_time)
                : ($sourcePost->post_date
                    ? Carbon::createFromTimestamp($sourcePost->post_date)
                    : Carbon::now()),
        ]);

        if (! $config->isDryRun) {
            $post->save();
            $this->cachePostMapping($sourcePost->pid, $post->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourcePost->pid,
                'target_id' => $post->id ?? 'N/A (dry run)',
                'topic' => $topic->title,
                'author' => $author instanceof User
                    ? $author->name
                    : 'Guest',
            ]);
        }
    }

    protected function findAuthor(object $sourcePost): ?User
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

    protected function modifyContent(string $content, MigrationConfig $config): string
    {
        if (! $config->downloadMedia) {
            return $content;
        }

        $baseUrl = $this->source->getBaseUrl();

        if (is_null($baseUrl)) {
            return $content;
        }

        return $this->parseAndReplaceImagesInHtml($content, function (string $imgSrc) use ($baseUrl): string {
            $cleanSrc = ltrim((string) preg_replace('/^<fileStore\.core_Attachment>\//', '', $imgSrc), '/');

            $filePath = $this->downloadAndStoreFile(
                baseUrl: $baseUrl.'/uploads',
                sourcePath: $cleanSrc,
                storagePath: 'forums/posts',
            );

            if (! is_null($filePath)) {
                return Storage::url($filePath);
            }

            return $imgSrc;
        });
    }

    protected function cachePostMapping(int $sourcePostId, int $targetPostId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourcePostId, $targetPostId, self::CACHE_TTL);
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();
        $config = $this->getConfig();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('queued', 0)
            ->orderBy('pid')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('author_id', $config->userId));
    }
}
