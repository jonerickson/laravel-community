<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Forum;
use App\Models\Topic;
use App\Models\User;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunityLanguageResolver;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TopicImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'topics';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:topic_map:';

    protected const string CACHE_TAG = 'migration:ic:topics';

    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    public function __construct(
        protected ?InvisionCommunityLanguageResolver $languageResolver = null,
    ) {}

    public static function getTopicMapping(int $sourceTopicId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceTopicId);
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
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->skip($offset));

        $totalTopics = $limit !== null && $limit !== 0 ? min($limit, $query->count()) : $query->count();

        $output->writeln("Found {$totalTopics} topics to migrate...");

        $progressBar = $output->createProgressBar($totalTopics);
        $progressBar->start();

        $processed = 0;

        $query
            ->lazyById($batchSize, 'tid')
            ->each(function ($sourceTopic) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): void {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return;
                }

                try {
                    $this->importTopic($sourceTopic, $isDryRun, $result);
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
            });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importTopic(object $sourceTopic, bool $isDryRun, MigrationResult $result): void
    {
        $title = $sourceTopic->title;
        $slug = $sourceTopic->title_seo ?? Str::slug($title);

        $existingTopic = Topic::query()
            ->where(function ($query) use ($title, $slug): void {
                $query->where('title', $title)
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existingTopic) {
            $this->cacheTopicMapping($sourceTopic->tid, $existingTopic->id);
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceTopic->tid,
                'title' => $title,
                'reason' => 'Already exists',
            ]);

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
            'title' => $title,
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

        if (! $isDryRun) {
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
}
