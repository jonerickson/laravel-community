<?php

declare(strict_types=1);

namespace App\Services\Migration;

use App\Services\Migration\Contracts\MigrationSource;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MigrationService
{
    /** @var array<string, MigrationSource> */
    protected array $sources = [];

    protected array $migratedEntities = [];

    protected array $optionalDependencies = [];

    protected ?MigrationConfig $config = null;

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

    public function configure(MigrationConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): ?MigrationConfig
    {
        return $this->config;
    }

    public function setOptionalDependencies(array $optionalDependencies): void
    {
        $this->optionalDependencies = $optionalDependencies;
    }

    public function getOptionalDependencies(MigrationSource $source, ?string $entity): array
    {
        $optional = [];
        $importers = is_null($entity) ? $source->getImporters() : [$entity => $source->getImporter($entity)];

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

    public function migrate(string $source, OutputStyle $output): MigrationResult
    {
        if (! isset($this->sources[$source])) {
            throw new InvalidArgumentException("Unknown migration source: $source");
        }

        if (! $this->config instanceof MigrationConfig) {
            throw new InvalidArgumentException('Migration config not set. Call configure() first.');
        }

        $migrationSource = $this->sources[$source];
        $result = new MigrationResult;
        $this->migratedEntities = [];

        $this->prepareForMigration($migrationSource);

        if (! is_null($this->config->entity)) {
            $this->migrateEntityWithDependencies($migrationSource, $this->config->entity, $output, $result);
        } else {
            foreach ($migrationSource->getImporters() as $importerEntity => $importer) {
                $this->migrateEntityWithDependencies($migrationSource, $importerEntity, $output, $result);
            }
        }

        return $result;
    }

    public function getImporterForEntity(string $entityName): ?Contracts\EntityImporter
    {
        foreach ($this->sources as $source) {
            $importer = $source->getImporter($entityName);

            if ($importer instanceof Contracts\EntityImporter) {
                return $importer;
            }
        }

        return null;
    }

    public function cleanup(): void
    {
        foreach ($this->sources as $source) {
            $source->cleanup();
        }
    }

    protected function migrateEntityWithDependencies(
        MigrationSource $source,
        string $entity,
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

        if ($importer->isCompleted()) {
            $output->writeln("<info>Skipping $entity - already completed</info>");
            $this->migratedEntities[] = $entity;

            return;
        }

        $dependencies = $importer->getDependencies();
        $preDependencies = array_filter($dependencies, fn (ImporterDependency $dep): bool => $dep->isPre());

        foreach ($preDependencies as $dependency) {
            if ($dependency->isRequired() || in_array($dependency->entityName, $this->optionalDependencies)) {
                $dependencyType = $dependency->isRequired() ? 'required' : 'optional';
                $output->writeln("<comment>Migrating {$dependency->entityName} ({$dependencyType} dependency of {$entity})...</comment>");
                $this->migrateEntityWithDependencies($source, $dependency->entityName, $output, $result);
            }
        }

        $this->migrateEntity($source, $entity, $output, $result);

        if ($this->config->limit === null && $this->config->offset === null && ! $this->config->isDryRun) {
            $importer->markCompleted();
        }

        $this->migratedEntities[] = $entity;

        $postDependencies = array_filter($dependencies, fn (ImporterDependency $dep): bool => $dep->isPost());

        foreach ($postDependencies as $dependency) {
            if ($dependency->isRequired() || in_array($dependency->entityName, $this->optionalDependencies)) {
                $dependencyType = $dependency->isRequired() ? 'required' : 'optional';
                $output->writeln("<comment>Migrating {$dependency->entityName} ({$dependencyType} dependency of {$entity})...</comment>");
                $this->migrateEntityWithDependencies($source, $dependency->entityName, $output, $result);
            }
        }
    }

    protected function migrateEntity(
        MigrationSource $source,
        string $entity,
        OutputStyle $output,
        MigrationResult $result,
    ): void {
        $importer = $source->getImporter($entity);

        if (! $importer instanceof Contracts\EntityImporter) {
            throw new InvalidArgumentException("Unknown entity: $entity");
        }

        $output->writeln("<info>Migrating $entity...</info>");

        $importer->import(
            config: $this->config,
            result: $result,
            output: $output,
        );
    }

    protected function prepareForMigration(MigrationSource $source): void
    {
        DB::connection($source->getConnection())->disableQueryLog();

        config()->set('mail.default', 'array');
        config()->set('logging.default', 'single');
        config()->set('logging.channels.single.path', storage_path('logs/migration.log'));
    }
}
