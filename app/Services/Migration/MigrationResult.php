<?php

declare(strict_types=1);

namespace App\Services\Migration;

class MigrationResult
{
    public function __construct(
        public array $entities = [],
        public array $skippedRecords = [],
        public array $failedRecords = [],
        public array $migratedRecords = [],
    ) {}

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
        if (! isset($this->skippedRecords[$entity])) {
            $this->skippedRecords[$entity] = [];
        }

        $this->skippedRecords[$entity][] = $record;
    }

    public function recordFailed(string $entity, array $record): void
    {
        if (! isset($this->failedRecords[$entity])) {
            $this->failedRecords[$entity] = [];
        }

        $this->failedRecords[$entity][] = $record;
    }

    public function recordMigrated(string $entity, array $record): void
    {
        if (! isset($this->migratedRecords[$entity])) {
            $this->migratedRecords[$entity] = [];
        }

        $this->migratedRecords[$entity][] = $record;
    }

    public function getSkippedRecords(string $entity): array
    {
        return $this->skippedRecords[$entity] ?? [];
    }

    public function getFailedRecords(string $entity): array
    {
        return $this->failedRecords[$entity] ?? [];
    }

    public function getMigratedRecords(string $entity): array
    {
        return $this->migratedRecords[$entity] ?? [];
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

    public function getTotalMigrated(): int
    {
        return array_sum(array_column($this->entities, 'migrated'));
    }

    public function getTotalSkipped(): int
    {
        return array_sum(array_column($this->entities, 'skipped'));
    }

    public function getTotalFailed(): int
    {
        return array_sum(array_column($this->entities, 'failed'));
    }
}
