<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MigrationResult
{
    public const string CACHE_TAG = 'migration:result';

    protected const int CACHE_TTL = 60 * 60 * 24 * 7;

    protected string $cachePrefix;

    public function __construct(
        public array $entities = [],
    ) {
        $this->cachePrefix = 'migration:result:'.Str::random().':';
    }

    public function addEntity(string $entity, int $migrated = 0, int $skipped = 0, int $failed = 0): void
    {
        if (! isset($this->entities[$entity])) {
            $this->entities[$entity] = [
                'migrated' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $this->entities[$entity]['migrated'] += $migrated;
        $this->entities[$entity]['skipped'] += $skipped;
        $this->entities[$entity]['failed'] += $failed;
    }

    public function recordSkipped(string $entity, array $record): void
    {
        $key = $this->cachePrefix.'skipped:'.$entity;
        $records = Cache::tags(self::CACHE_TAG)->get($key, []);
        $records[] = $record;
        Cache::tags(self::CACHE_TAG)->put($key, $records, self::CACHE_TTL);
    }

    public function recordFailed(string $entity, array $record): void
    {
        $key = $this->cachePrefix.'failed:'.$entity;
        $records = Cache::tags(self::CACHE_TAG)->get($key, []);
        $records[] = $record;
        Cache::tags(self::CACHE_TAG)->put($key, $records, self::CACHE_TTL);
    }

    public function recordMigrated(string $entity, array $record): void
    {
        $key = $this->cachePrefix.'migrated:'.$entity;
        $records = Cache::tags(self::CACHE_TAG)->get($key, []);
        $records[] = $record;
        Cache::tags(self::CACHE_TAG)->put($key, $records, self::CACHE_TTL);
    }

    public function getSkippedRecords(string $entity): array
    {
        return Cache::tags(self::CACHE_TAG)->get($this->cachePrefix.'skipped:'.$entity, []);
    }

    public function getFailedRecords(string $entity): array
    {
        return Cache::tags(self::CACHE_TAG)->get($this->cachePrefix.'failed:'.$entity, []);
    }

    public function getMigratedRecords(string $entity): array
    {
        return Cache::tags(self::CACHE_TAG)->get($this->cachePrefix.'migrated:'.$entity, []);
    }

    public function incrementMigrated(string $entity, int $count = 1): void
    {
        $this->addEntity($entity, migrated: $count);
    }

    public function incrementSkipped(string $entity, int $count = 1): void
    {
        $this->addEntity($entity, skipped: $count);
    }

    public function incrementFailed(string $entity, int $count = 1): void
    {
        $this->addEntity($entity, failed: $count);
    }

    public function toTableRows(): array
    {
        $rows = [];

        foreach ($this->entities as $entity => $stats) {
            $rows[] = [
                $entity,
                $stats['migrated'],
                $stats['skipped'],
                $stats['failed'],
            ];
        }

        return $rows;
    }

    public function cleanup(): void
    {
        Cache::tags(self::CACHE_TAG)->flush();
    }
}
