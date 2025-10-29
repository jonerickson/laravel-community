<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Group;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunityLanguageResolver;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GroupImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'groups';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:group_map:';

    protected const string CACHE_TAG = 'migration:ic:groups';

    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    protected array $batchCache = [];

    public function __construct(
        protected ?InvisionCommunityLanguageResolver $languageResolver = null,
    ) {}

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

        $query = DB::connection($connection)
            ->table($this->getSourceTable())
            ->when($offset !== null && $offset !== 0, fn (Builder $builder) => $builder->skip($offset));

        $totalGroups = $limit !== null && $limit !== 0 ? min($limit, $query->count()) : $query->count();

        $output->writeln("Found {$totalGroups} groups to migrate...");

        $progressBar = $output->createProgressBar($totalGroups);
        $progressBar->start();

        $processed = 0;

        $query
            ->lazyById($batchSize, 'g_id')
            ->each(function (object $sourceGroup) use ($isDryRun, $result, $progressBar, $output, &$processed): void {
                try {
                    $this->importGroup($sourceGroup, $isDryRun, $result);
                } catch (Exception $e) {
                    $result->incrementFailed(self::ENTITY_NAME);
                    $result->recordFailed(self::ENTITY_NAME, [
                        'source_id' => $sourceGroup->g_id ?? 'unknown',
                        'name' => $sourceGroup->g_name ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    Log::error('Failed to import group', [
                        'source_id' => $sourceGroup->g_id ?? 'unknown',
                        'name' => $sourceGroup->g_name ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $fileName = Str::of($e->getFile())->classBasename();
                    $output->writeln("<error>Failed to import group: {$e->getMessage()} in $fileName on Line {$e->getLine()}.</error>");
                }

                $processed++;
                $progressBar->advance();
            });

        $progressBar->finish();
        $output->newLine(2);
    }

    public function cleanup(): void
    {
        Cache::tags(self::CACHE_TAG)->flush();
    }

    protected function importGroup(object $sourceGroup, bool $isDryRun, MigrationResult $result): void
    {
        $name = $this->languageResolver?->resolveGroupName($sourceGroup->g_id) ?? "Invision Group $sourceGroup->g_id";

        $existingGroup = Group::query()->where('name', $name)->first();

        if ($existingGroup) {
            $this->cacheGroupMapping($sourceGroup->g_id, $existingGroup->id);
            $result->incrementSkipped(self::ENTITY_NAME);
            $result->recordSkipped(self::ENTITY_NAME, [
                'source_id' => $sourceGroup->g_id,
                'name' => $name,
                'reason' => 'Already exists',
            ]);

            return;
        }

        $group = new Group;
        $group->forceFill([
            'name' => $name,
            'description' => 'An Invision Community migrated group.',
            'color' => $this->convertColor($sourceGroup->prefix ?? ''),
            'is_active' => true,
            'is_default_guest' => false,
            'is_default_member' => false,
        ]);

        if (! $isDryRun) {
            $group->save();
            $this->cacheGroupMapping($sourceGroup->g_id, $group->id);
        }

        $result->incrementMigrated(self::ENTITY_NAME);
        $result->recordMigrated(self::ENTITY_NAME, [
            'source_id' => $sourceGroup->g_id,
            'target_id' => $group->id ?? 'N/A (dry run)',
            'name' => $group->name,
            'color' => $group->color,
        ]);
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
        Cache::put(self::CACHE_KEY_PREFIX.$sourceGroupId, $targetGroupId, 60 * 60 * 24 * 7);
    }
}
