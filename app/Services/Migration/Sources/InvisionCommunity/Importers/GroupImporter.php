<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Group;
use App\Services\Migration\AbstractImporter;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunitySource;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GroupImporter extends AbstractImporter
{
    public const string ENTITY_NAME = 'groups';

    public const string CACHE_KEY_PREFIX = 'migration:ic:group_map:';

    public const string CACHE_TAG = 'migration:ic:groups';

    public static function getGroupMapping(int $sourceGroupId): ?int
    {
        return (int) Cache::tags(self::CACHE_TAG)->get(self::CACHE_KEY_PREFIX.$sourceGroupId);
    }

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getSourceTable(): string
    {
        return 'core_groups';
    }

    /**
     * @return array{}
     */
    public function getDependencies(): array
    {
        return [];
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
            ->when($config->offset !== null && $config->offset !== 0, fn (Builder $builder) => $builder->offset($config->offset))
            ->when($config->limit !== null && $config->limit !== 0, fn (Builder $builder) => $builder->limit($config->limit));

        $totalGroups = $baseQuery->clone()->countOffset();

        if ($output->isVerbose()) {
            $components->info(sprintf('Found %s groups to migrate...', $totalGroups));
        }

        $progressBar = $output->createProgressBar($totalGroups);
        $progressBar->start();

        $processed = 0;

        $baseQuery->chunk($config->batchSize, function ($groups) use ($config, $result, $progressBar, $output, $components, &$processed): bool {
            foreach ($groups as $sourceGroup) {
                if ($config->limit !== null && $config->limit !== 0 && $processed >= $config->limit) {
                    return false;
                }

                try {
                    $this->importGroup($sourceGroup, $config, $result, $output);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);

                    if ($output->isVerbose()) {
                        $result->recordFailed(self::ENTITY_NAME, [
                            'source_id' => $sourceGroup->g_id ?? 'unknown',
                            'name' => $sourceGroup->g_name ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }

                    Log::error('Failed to import group', [
                        'source_id' => $sourceGroup->g_id ?? 'unknown',
                        'name' => $sourceGroup->g_name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $output->newLine(2);
                    $fileName = Str::of($e->getFile())->classBasename();
                    $components->error(sprintf('Failed to import group: %s in %s on Line %d.', $e->getMessage(), $fileName, $e->getLine()));
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

    protected function importGroup(object $sourceGroup, MigrationConfig $config, MigrationResult $result, OutputStyle $output): void
    {
        $name = Str::of($this->source instanceof InvisionCommunitySource
                ? $this->source->getLanguageResolver()->resolveGroupName($sourceGroup->g_id, 'Invision Group '.$sourceGroup->g_id)
                : 'Invision Group '.$sourceGroup->g_id)
            ->trim()
            ->limit(255, '')
            ->toString();

        $existingGroup = Group::query()->where('name', $name)->first();

        if ($existingGroup) {
            $this->cacheGroupMapping($sourceGroup->g_id, $existingGroup->id);
            $result->incrementSkipped(self::ENTITY_NAME);

            if ($output->isVerbose()) {
                $result->recordSkipped(self::ENTITY_NAME, [
                    'source_id' => $sourceGroup->g_id,
                    'name' => $name,
                    'reason' => 'Already exists',
                ]);
            }

            return;
        }

        $group = new Group;
        $group->forceFill([
            'name' => $name,
            'description' => null,
            'color' => $this->convertColor($sourceGroup->prefix ?? ''),
            'is_active' => true,
            'is_default_guest' => false,
            'is_default_member' => false,
        ]);

        if (! $config->isDryRun) {
            $group->save();
            $this->cacheGroupMapping($sourceGroup->g_id, $group->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);

        if ($output->isVeryVerbose()) {
            $result->recordMigrated(self::ENTITY_NAME, [
                'source_id' => $sourceGroup->g_id,
                'target_id' => $group->id ?? 'N/A (dry run)',
                'name' => $group->name,
                'color' => $group->color,
            ]);
        }
    }

    protected function convertColor(?string $prefix): string
    {
        if (blank($prefix)) {
            return '#94a3b8';
        }

        if (preg_match('/#([0-9a-fA-F]{6})/', $prefix, $matches)) {
            return '#'.$matches[1];
        }

        return '#94a3b8';
    }

    protected function cacheGroupMapping(int $sourceGroupId, int $targetGroupId): void
    {
        Cache::tags(self::CACHE_TAG)->put(self::CACHE_KEY_PREFIX.$sourceGroupId, $targetGroupId, 60 * 60 * 24 * 7);
    }

    protected function getBaseQuery(): Builder
    {
        $connection = $this->source->getConnection();

        return DB::connection($connection)
            ->table($this->getSourceTable())
            ->orderBy('g_id');
    }
}
