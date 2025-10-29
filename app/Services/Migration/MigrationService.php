<?php

declare(strict_types=1);

namespace App\Services\Migration;

use App\Services\Migration\Contracts\MigrationSource;
use Illuminate\Console\OutputStyle;
use InvalidArgumentException;

class MigrationService
{
    protected array $sources = [];

    protected array $migratedEntities = [];

    protected array $optionalDependencies = [];

    public function registerSource(MigrationSource $source): void
    {
        $this->sources[$source->getName()] = $source;
    }

    public function getAvailableSources(): array
    {
        return array_keys($this->sources);
    }

    public function getSource(string $name): ?MigrationSource
    {
        return $this->sources[$name] ?? null;
    }

    public function setOptionalDependencies(array $optionalDependencies): void
    {
        $this->optionalDependencies = $optionalDependencies;
    }

    public function getOptionalDependencies(MigrationSource $source, ?string $entity): array
    {
        $optional = [];
        $importers = $entity !== null && $entity !== '' && $entity !== '0' ? [$entity => $source->getImporter($entity)] : $source->getImporters();

        foreach ($importers as $importer) {
            if (! $importer) {
                continue;
            }

            foreach ($importer->getDependencies() as $dependency) {
                if ($dependency->isOptional()) {
                    $optional[$dependency->entityName] = $dependency;
                }
            }
        }

        return $optional;
    }

    public function migrate(
        string $source,
        ?string $entity,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
        OutputStyle $output,
    ): MigrationResult {
        if (! isset($this->sources[$source])) {
            throw new InvalidArgumentException("Unknown migration source: $source");
        }

        $migrationSource = $this->sources[$source];
        $result = new MigrationResult;
        $this->migratedEntities = [];

        if ($entity !== null && $entity !== '' && $entity !== '0') {
            $this->migrateEntityWithDependencies($migrationSource, $entity, $batchSize, $limit, $offset, $isDryRun, $output, $result);
        } else {
            foreach ($migrationSource->getImporters() as $importerEntity => $importer) {
                $this->migrateEntityWithDependencies($migrationSource, $importerEntity, $batchSize, $limit, $offset, $isDryRun, $output, $result);
            }
        }

        return $result;
    }

    public function cleanup(): void
    {
        foreach ($this->sources as $source) {
            foreach ($source->getImporters() as $importer) {
                $importer->cleanup();
            }
        }
    }

    protected function migrateEntityWithDependencies(
        MigrationSource $source,
        string $entity,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        if (in_array($entity, $this->migratedEntities)) {
            return;
        }

        $importer = $source->getImporter($entity);

        if (! $importer instanceof Contracts\EntityImporter) {
            throw new InvalidArgumentException("Unknown entity: $entity");
        }

        $dependencies = $importer->getDependencies();
        $preDependencies = array_filter($dependencies, fn (ImporterDependency $dep): bool => $dep->isPre());

        foreach ($preDependencies as $dependency) {
            if ($dependency->isRequired() || in_array($dependency->entityName, $this->optionalDependencies)) {
                $dependencyType = $dependency->isRequired() ? 'required' : 'optional';
                $output->writeln("<comment>Migrating {$dependency->entityName} ({$dependencyType} dependency of {$entity})...</comment>");
                $this->migrateEntityWithDependencies($source, $dependency->entityName, $batchSize, $limit, $offset, $isDryRun, $output, $result);
            }
        }

        $this->migrateEntity($source, $entity, $batchSize, $limit, $offset, $isDryRun, $output, $result);

        $this->migratedEntities[] = $entity;

        $postDependencies = array_filter($dependencies, fn (ImporterDependency $dep): bool => $dep->isPost());

        foreach ($postDependencies as $dependency) {
            if ($dependency->isRequired() || in_array($dependency->entityName, $this->optionalDependencies)) {
                $dependencyType = $dependency->isRequired() ? 'required' : 'optional';
                $output->writeln("<comment>Migrating {$dependency->entityName} ({$dependencyType} dependency of {$entity})...</comment>");
                $this->migrateEntityWithDependencies($source, $dependency->entityName, $batchSize, $limit, $offset, $isDryRun, $output, $result);
            }
        }
    }

    protected function migrateEntity(
        MigrationSource $source,
        string $entity,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        $importer = $source->getImporter($entity);

        if (! $importer instanceof Contracts\EntityImporter) {
            throw new InvalidArgumentException("Unknown entity: $entity");
        }

        $output->writeln("<info>Migrating $entity...</info>");

        $importer->import(
            connection: $source->getConnection(),
            batchSize: $batchSize,
            limit: $limit,
            offset: $offset,
            isDryRun: $isDryRun,
            output: $output,
            result: $result,
        );
    }
}
