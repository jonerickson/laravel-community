<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Enums\Role;
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
    ): void {
        $config = $this->getConfig();

        $baseQuery = $this->getBaseQuery()
            ->when($config->offset !== null && $config->offset !== 0, fn ($builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn ($builder) => $builder->limit($config->limit));

        $totalTopics = $baseQuery->clone()->countOffset();

        $output->writeln("Found {$totalTopics} topics to migrate...");

        $progressBar = $output->createProgressBar($totalTopics);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($topics) use ($config, $result, $progressBar, $output, &$processed): bool {
            foreach ($topics as $sourceTopic) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importTopic($sourceTopic, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourceTopic->tid ?? 'unknown',
                        'title' => $sourceTopic->title ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import topic', [
                        'source_id' => $sourceTopic->tid ?? 'unknown',
                        'title' => $sourceTopic->title ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import topic: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine();
        $output->writeln("Migrated $processed topics...");
        $output->newLine();
    }

    protected function importTopic(object $sourceTopic, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $title = $sourceTopic->title;

        $slug = Str::of($sourceTopic->title_seo ?? $title)
            ->slug()
            ->toString();

        $existingTopic = Topic::query()->where('slug', $slug)->first();

        if ($existingTopic) {
            $this->cacheTopicMapping($sourceTopic->tid, $existingTopic->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVeryVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceTopic->tid,
                    'title' => $title,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $author = $this->findOrCreateAuthor($sourceTopic);

        if (! $author instanceof User) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourceTopic->tid,
                'title' => $title,
                'error' => 'Could not find or create author',
            ]);

            return;
        }

        $forum = $this->findOrCreateForum($sourceTopic);

        if (! $forum instanceof Forum) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourceTopic->tid,
                'title' => $title,
                'error' => 'Could not find or create forum',
            ]);

            return;
        }

        $topic = new Topic;
        $topic->forceFill([
            'title' => Str::trim($title),
            'slug' => $slug,
            'forum_id' => $forum->id,
            'is_pinned' => $sourceTopic->pinned,
            'is_locked' => false,
            'created_by' => $author->id,
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
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourceTopic->tid,
            'target_id' => $topic->id ?? 'N/A (dry run)',
            'title' => $topic->title,
            'slug' => $topic->slug,
            'author' => $author->name,
        ]);
    }

    protected function findOrCreateAuthor(object $sourceTopic): ?User
    {
        $mappedUserId = UserImporter::getUserMapping((int) $sourceTopic->starter_id);

        if ($mappedUserId !== null && $mappedUserId !== 0) {
            return User::query()->find($mappedUserId);
        }

        if ($adminUser = User::query()->role(Role::Administrator)->oldest()->first()) {
            return $adminUser;
        }

        return null;
    }

    protected function findOrCreateForum(object $sourceTopic): ?Forum
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
