<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Forum;
use App\Models\ForumCategory;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunitySource;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ForumImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'forums';

    public const string CACHE_KEY_PREFIX = 'migration:ic:forum_map:';

    public const string CACHE_KEY_CATEGORY_PREFIX = 'migration:ic:forum_category_map:';

    public const string CACHE_TAG = 'migration:ic:forums';

    public static function getForumMapping(int $sourceForumId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceForumId);
    }

    public static function getCategoryMapping(int $sourceCategoryId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_CATEGORY_PREFIX.$sourceCategoryId);
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
        return 'forums_forums';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function import(
        MigrationConfig $config,
        MigrationResult $result,
        OutputStyle $output,
    ): void {
        $this->importCategories($config, $result, $output);

        $connection = $this->source->getConnection();

        $baseQuery = DB::connection($connection)
            ->table($this->getSourceTable())
            ->where('parent_id', '<>', -1)
            ->where('position', '<>', 0)
            ->orderBy('id')
            ->when($config->offset !== null && $config->offset !== 0, fn ($builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn ($builder) => $builder->limit($config->limit));

        $totalForums = $baseQuery->count();

        $output->writeln("Found $totalForums forums to migrate...");

        $progressBar = $output->createProgressBar($totalForums);
        $progressBar->start();

        $processed = 0;
        $sourceForumsData = [];

        $baseQuery->chunk($config->batchSize, function ($forums) use ($config, $result, $progressBar, $output, &$processed, &$sourceForumsData): bool {
            foreach ($forums as $sourceForum) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                $sourceForumsData[] = $sourceForum;

                try {
                    $this->importForum($sourceForum, $config, $result, $output);
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
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine();
        $output->writeln("Migrated $processed forums...");
        $output->newLine();

        $this->updateForumParentRelationships($sourceForumsData, $config, $output);
    }

    protected function importCategories(
        MigrationConfig $config,
        MigrationResult $result,
        OutputStyle $output,
    ): void {
        $connection = $this->source->getConnection();

        $baseQuery = DB::connection($connection)
            ->table('forums_forums')
            ->where('parent_id', -1)
            ->where('position', '<>', 0)
            ->orderBy('id')
            ->when($config->offset !== null && $config->offset !== 0, fn ($builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn ($builder) => $builder->limit($config->limit));

        $totalCategories = $baseQuery->count();

        $output->writeln("Found {$totalCategories} forum categories to migrate...");

        $progressBar = $output->createProgressBar($totalCategories);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($categories) use ($config, $result, $progressBar, $output, &$processed): bool {
            foreach ($categories as $sourceCategory) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importCategory($sourceCategory, $config, $result, $output);
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
            }

            return true;
        });

        $progressBar->finish();

        $output->newLine();
        $output->writeln("Migrated $processed forum categories...");
        $output->newLine();
    }

    protected function importCategory(object $sourceCategory, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $name = $this->source instanceof InvisionCommunitySource
            ? $this->source->getLanguageResolver()->resolveForumName($sourceCategory->id, "Invision Forum Category $sourceCategory->id")
            : "Invision Forum Category $sourceCategory->id";

        $description = $this->source instanceof InvisionCommunitySource
            ? $this->source->getLanguageResolver()->resolveForumDescription($sourceCategory->id)
            : null;

        $slug = Str::of($sourceCategory->name_seo ?? $name)
            ->slug()
            ->toString();

        $existingCategory = ForumCategory::query()->where('slug', $slug)->first();

        if ($existingCategory) {
            $this->cacheCategoryMapping($sourceCategory->id, $existingCategory->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVeryVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceCategory->id,
                    'name' => $name,
                    'slug' => $slug,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $category = new ForumCategory;
        $category->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => strip_tags($description ?? ''),
            'icon' => 'message-square',
            'color' => $sourceCategory->feature_color ?? '#94a3b8',
            'is_active' => true,
        ]);

        if (! $config->isDryRun) {
            $category->save();
            $category->groups()->sync([GroupImporter::getDefaultMemberGroup(), GroupImporter::getDefaultGuestGroup()]);
            $this->cacheCategoryMapping($sourceCategory->id, $category->id);

            if (($imagePath = $sourceCategory->card_image) && ($baseUrl = $this->source->getBaseUrl()) && $config->downloadMedia) {
                $filePath = $this->downloadAndStoreFile(
                    baseUrl: $baseUrl.'/uploads',
                    sourcePath: $imagePath,
                    storagePath: 'forums/categories',
                );

                if (! is_null($filePath)) {
                    $category->featured_image = $filePath;
                    $category->save();
                }
            }
        }

        $result->incrementMigrated('forum_categories');
        $result->recordMigrated('forum_categories', [
            'source_id' => $sourceCategory->id,
            'target_id' => $category->id ?? 'N/A (dry run)',
            'name' => $category->name,
            'slug' => $category->slug,
        ]);
    }

    protected function importForum(object $sourceForum, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $name = $this->source instanceof InvisionCommunitySource
            ? $this->source->getLanguageResolver()->resolveForumName($sourceForum->id, "Invision Forum $sourceForum->id")
            : "Invision Forum $sourceForum->id";

        $description = $this->source instanceof InvisionCommunitySource
            ? $this->source->getLanguageResolver()->resolveForumDescription($sourceForum->id)
            : null;

        $slug = Str::of($sourceForum->name_seo ?? $name)
            ->slug()
            ->toString();

        $existingForum = Forum::query()->where('slug', $slug)->first();

        if ($existingForum) {
            $this->cacheForumMapping($sourceForum->id, $existingForum->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVeryVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceForum->id,
                    'name' => $name,
                    'slug' => $slug,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $forum = new Forum;
        $forum->forceFill([
            'name' => $name,
            'slug' => $slug,
            'description' => strip_tags($description ?? ''),
            'icon' => 'message-square',
            'color' => $sourceForum->feature_color ?? '#94a3b8',
            'order' => $sourceForum->position ?? 0,
            'is_active' => true,
        ]);

        if (! $config->isDryRun) {
            $forum->save();
            $forum->groups()->sync([GroupImporter::getDefaultMemberGroup(), GroupImporter::getDefaultGuestGroup()]);
            $this->cacheForumMapping($sourceForum->id, $forum->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourceForum->id,
            'target_id' => $forum->id ?? 'N/A (dry run)',
            'name' => $forum->name,
            'slug' => $forum->slug,
        ]);
    }

    protected function updateForumParentRelationships(array $sourceForumsData, MigrationConfig $config, OutputStyle $output): void
    {
        $output->writeln('Updating forum parent relationships...');

        $progressBar = $output->createProgressBar(count($sourceForumsData));
        $progressBar->start();

        foreach ($sourceForumsData as $sourceForum) {
            try {
                $mappedForumId = static::getForumMapping((int) $sourceForum->id);

                if ($mappedForumId === null || $mappedForumId === 0) {
                    $progressBar->advance();

                    continue;
                }

                $parentCategoryId = static::getCategoryMapping((int) $sourceForum->parent_id);

                if ($parentCategoryId !== null && $parentCategoryId !== 0) {
                    if (! $config->isDryRun) {
                        Forum::query()
                            ->where('id', $mappedForumId)
                            ->update(['category_id' => $parentCategoryId]);
                    }
                } else {
                    $parentForumId = static::getForumMapping((int) $sourceForum->parent_id);

                    if ($parentForumId !== null && $parentForumId !== 0 && ! $config->isDryRun) {
                        Forum::query()
                            ->where('id', $mappedForumId)
                            ->update(['parent_id' => $parentForumId]);
                    }
                }
            } catch (Exception $e) {
                Log::error('Failed to update forum parent relationship', [
                    'source_id' => $sourceForum->id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->newLine(2);
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
