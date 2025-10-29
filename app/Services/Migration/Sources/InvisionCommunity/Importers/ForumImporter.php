<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Forum;
use App\Models\ForumCategory;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunityLanguageResolver;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ForumImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'forums';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:forum_map:';

    protected const string CACHE_KEY_CATEGORY_PREFIX = 'migration:ic:forum_category_map:';

    protected const string CACHE_TAG = 'migration:ic:forums';

    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    public function __construct(
        protected ?InvisionCommunityLanguageResolver $languageResolver = null,
    ) {}

    public static function getForumMapping(int $sourceForumId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceForumId);
    }

    public static function getCategoryMapping(int $sourceCategoryId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_CATEGORY_PREFIX.$sourceCategoryId);
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
        return 'forums_forums';
    }

    public function getDependencies(): array
    {
        return [];
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

        $this->importCategories($connection, $batchSize, $limit, $offset, $isDryRun, $output, $result);

        $query = DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('parent_id', '<>', -1)
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->skip($offset));

        $totalForums = $limit !== null && $limit !== 0 ? min($limit, $query->count()) : $query->count();

        $output->writeln("Found {$totalForums} forums to migrate...");

        $progressBar = $output->createProgressBar($totalForums);
        $progressBar->start();

        $processed = 0;

        $query
            ->lazyById($batchSize, 'id')
            ->each(function ($sourceForum) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): void {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return;
                }

                try {
                    $this->importForum($sourceForum, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourceForum->id ?? 'unknown',
                        'name' => $sourceForum->name ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import forum', [
                        'source_id' => $sourceForum->id ?? 'unknown',
                        'name' => $sourceForum->name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import forum: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importCategories(
        string $connection,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        $query = DB::connection($connection)
            ->table('forums_forums')
            ->where('parent_id', -1)
            ->when($offset !== null && $offset !== 0, fn ($builder) => $builder->skip($offset));

        $totalCategories = $limit !== null && $limit !== 0 ? min($limit, $query->count()) : $query->count();

        $output->writeln("Found {$totalCategories} forum categories to migrate...");

        $progressBar = $output->createProgressBar($totalCategories);
        $progressBar->start();

        $processed = 0;

        $query
            ->lazyById($batchSize, 'id')
            ->each(function ($sourceCategory) use ($limit, $isDryRun, $result, $progressBar, $output, &$processed): void {
                if ($limit !== null && $limit !== 0 && $processed >= $limit) {
                    return;
                }

                try {
                    $this->importCategory($sourceCategory, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed('forum_categories');
                    $result->recordFailed('forum_categories', [
                        'source_id' => $sourceCategory->id ?? 'unknown',
                        'name' => $sourceCategory->name ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import forum category', [
                        'source_id' => $sourceCategory->id ?? 'unknown',
                        'name' => $sourceCategory->name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import forum category: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            });

        $progressBar->finish();
        $output->newLine(2);
    }

    protected function importCategory(object $sourceCategory, bool $isDryRun, MigrationResult $result): void
    {
        $name = $this->languageResolver?->resolveForumName($sourceCategory->id) ?? "Invision Forum Category $sourceCategory->id";
        $slug = $sourceCategory->name_seo ?? Str::slug($name);

        $existingCategory = ForumCategory::query()
            ->where(function ($query) use ($name, $slug): void {
                $query->where('name', $name)
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existingCategory) {
            $this->cacheCategoryMapping($sourceCategory->id, $existingCategory->id);
            $result->incrementSkipped('forum_categories');
            $result->recordSkipped('forum_categories', [
                'source_id' => $sourceCategory->id,
                'name' => $name,
                'reason' => 'Already exists',
            ]);

            return;
        }

        $category = new ForumCategory;
        $category->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'icon' => 'message-square',
            'color' => '#94a3b8',
            'is_active' => true,
        ]);

        if (! $isDryRun) {
            $category->save();
            $this->cacheCategoryMapping($sourceCategory->id, $category->id);
        }

        $result->incrementMigrated('forum_categories');
        $result->recordMigrated('forum_categories', [
            'source_id' => $sourceCategory->id,
            'target_id' => $category->id ?? 'N/A (dry run)',
            'name' => $category->name,
            'slug' => $category->slug,
        ]);
    }

    protected function importForum(object $sourceForum, bool $isDryRun, MigrationResult $result): void
    {
        $name = $this->languageResolver->resolveForumName($sourceForum->id) ?? "Invision Forum $sourceForum->id";
        $slug = $sourceForum->name_seo ?? Str::slug($name);

        $existingForum = Forum::query()
            ->where(function ($query) use ($name, $slug): void {
                $query->where('name', $name)
                    ->orWhere('slug', $slug);
            })
            ->first();

        if ($existingForum) {
            $this->cacheForumMapping($sourceForum->id, $existingForum->id);
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceForum->id,
                'name' => $name,
                'reason' => 'Already exists',
            ]);

            return;
        }

        $category = $this->findOrCreateCategory($sourceForum);

        if (! $category instanceof ForumCategory) {
            $result->incrementFailed(self::ENTITY_NAME);
            $result->recordFailed(self::ENTITY_NAME, [
                'source_id' => $sourceForum->id,
                'name' => $name,
                'error' => 'Could not find or create category',
            ]);

            return;
        }

        $forum = new Forum;
        $forum->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'category_id' => $category->id,
            'icon' => 'message-square',
            'color' => '#94a3b8',
            'order' => $sourceForum->position ?? 0,
            'is_active' => true,
        ]);

        if (! $isDryRun) {
            $forum->save();
            $this->cacheForumMapping($sourceForum->id, $forum->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourceForum->id,
            'target_id' => $forum->id ?? 'N/A (dry run)',
            'name' => $forum->name,
            'slug' => $forum->slug,
            'category' => $category->name,
        ]);
    }

    protected function findOrCreateCategory(object $sourceForum): ?ForumCategory
    {
        $mappedCategoryId = static::getCategoryMapping((int) $sourceForum->parent_id);

        if ($mappedCategoryId !== null && $mappedCategoryId !== 0) {
            return ForumCategory::query()->find($mappedCategoryId);
        }

        return null;
    }

    protected function cacheForumMapping(int $sourceForumId, int $targetForumId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceForumId, $targetForumId, self::CACHE_TTL);
    }

    protected function cacheCategoryMapping(int $sourceCategoryId, int $targetCategoryId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_CATEGORY_PREFIX.$sourceCategoryId, $targetCategoryId, self::CACHE_TTL);
    }
}
