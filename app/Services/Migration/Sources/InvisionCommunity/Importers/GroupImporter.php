<?php

declare(strict_types=1);

namespace App\Services\Migration\Sources\InvisionCommunity\Importers;

use App\Models\Group;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\Sources\InvisionCommunity\InvisionCommunityLanguageResolver;
use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GroupImporter implements EntityImporter
{
    protected const string ENTITY_NAME = 'groups';

    protected const string CACHE_KEY_PREFIX = 'migration:ic:group_map:';

    public function __construct(
        protected ?InvisionCommunityLanguageResolver $languageResolver = null,
    ) {}

    public static function getGroupMapping(int $sourceGroupId): ?int
    {
        return Cache::get(self::CACHE_KEY_PREFIX.$sourceGroupId);
    }

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function import(
        string $connection,
        int $batchSize,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        if (! $this->languageResolver instanceof InvisionCommunityLanguageResolver) {
            $this->languageResolver = new InvisionCommunityLanguageResolver($connection);
        }

        $totalGroups = DB::connection($connection)
            ->table('core_groups')
            ->count();

        $output->writeln("Found {$totalGroups} groups to migrate...");

        $progressBar = $output->createProgressBar($totalGroups);
        $progressBar->start();

        DB::connection($connection)
            ->table('core_groups')
            ->orderBy('g_id')
            ->chunk($batchSize, function ($sourceGroups) use ($isDryRun, $result, $progressBar, $output): void {
                foreach ($sourceGroups as $sourceGroup) {
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

                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $output->newLine(2);
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
        Cache::forever(self::CACHE_KEY_PREFIX.$sourceGroupId, $targetGroupId);
    }
}
