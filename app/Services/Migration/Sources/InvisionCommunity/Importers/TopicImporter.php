<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Forum;
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
use Illuminate\Support\Str;

class TopicImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'topics';

    public const string CACHE_KEY_PREFIX = 'migration:ic:topic_map:';

    public const string CACHE_TAG = 'migration:ic:topics';

    public static function getTopicMapping(int $sourceTopicId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceTopicId);
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
        return 'forums_topics';
    }

    /**
     * @return ImporterDependency[]
     */
    public function getDependencies(): array
    {
        return [
            ImporterDependency::requiredPre('users', 'Topics require users to exist for author assignment'),
            ImporterDependency::requiredPre('forums', 'Topics require that forums exist for proper assignment'),
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

        $totalTopics = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s topics to migrate...', $totalTopics));
        }

        $progressBar = $output->createProgressBar($totalTopics);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($topics) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($topics as $sourceTopic) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importTopic($sourceTopic, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceTopic->tid ?? 'unknown',
                            'title' => $sourceTopic->title ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import topic', [
                        'source_id' => $sourceTopic->tid ?? 'unknown',
                        'title' => $sourceTopic->title ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import topic: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
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

    protected function importTopic(object $sourceTopic, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $title = Str::of($sourceTopic->title)
            ->trim()
            ->limit(255, '')
            ->toString();

        $slug = Str::of($sourceTopic->title_seo ?? $title)
            ->trim()
            ->limit(25, '')
            ->slug()
            ->toString();

        $existingTopic = Topic::query()
            ->where('slug', $slug)
            ->first();

        if ($existingTopic) {
            if ($existingTopic->created_at->getTimestamp() === $sourceTopic->start_date) {
                $this->cacheTopicMapping($sourceTopic->tid, $existingTopic->id);
                $result->incrementSkipped(self::ENTITY_NAME);

                if ($output->isVerbose()) {
                    $result->recordSkipped(self::ENTITY_NAME, [
                        'source_id' => $sourceTopic->tid,
                        'title' => $title,
                        'reason' => 'Already exists',
                    ]);
                }

                return;
            }

            $slug = Str::of($slug)
                ->trim()
                ->limit(25, '')
                ->unique('topics', 'slug')
                ->slug()
                ->toString();
        }

        $forum = $this->findForum($sourceTopic);

        if (! $forum instanceof Forum) {
            $result->incrementFailed(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordFailed(self::ENTITY_NAME, [
                    'source_id' => $sourceTopic->tid,
                    'title' => $title,
                    'error' => 'Could not find or create forum',
                ]);
            }

            return;
        }

        $author = $this->findAuthor($sourceTopic);

        $topic = new Topic;
        $topic->forceFill([
            'title' => $title,
            'slug' => $slug,
            'forum_id' => $forum->id,
            'is_pinned' => $sourceTopic->pinned,
            'is_locked' => false,
            'created_by' => $author instanceof User
                ? $author->id
                : null,
            'created_at' => $sourceTopic->start_date
                ? Carbon::createFromTimestamp($sourceTopic->start_date)
                : Carbon::now(),
            'updated_at' => $sourceTopic->last_post
                ? Carbon::createFromTimestamp($sourceTopic->last_post)
                : Carbon::now(),
        ]);

        if (! $config->isDryRun) {
            $topic->save();
            $this->cacheTopicMapping($sourceTopic->tid, $topic->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceTopic->tid,
                'target_id' => $topic->id ?? 'N/A (dry run)',
                'title' => $topic->title,
                'slug' => $topic->slug,
                'author' => $author instanceof User
                    ? $author->name
                    : 'Guest',
            ]);
        }
    }

    protected function findAuthor(object $sourceTopic): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourceTopic->starter_id);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        return null;
    }

    protected function findForum(object $sourceTopic): ?Forum
    {
        $mappedForumId = ForumImporter::getForumMapping((int) $sourceTopic->forum_id);

        if ($mappedForumId !== null && $mappedForumId !== 0) {
            return Forum::query()->find($mappedForumId);
        }

        return null;
    }

    protected function cacheTopicMapping(int $sourceTopicId, int $targetTopicId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceTopicId, $targetTopicId, self::CACHE_TTL);
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();
        $config = $this->getConfig();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->orderBy('tid')
            ->when($config->userId !== null && $config->userId !== 0, fn ($builder) => $builder->where('starter_id', $config->userId));
    }
}
