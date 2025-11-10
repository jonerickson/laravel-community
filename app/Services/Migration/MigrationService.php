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

    protected ?OutputStyle $output = null;

    public function registerSource(MigrationSource $source): void
    {
        $this->sources[$source->getName()] = $source;
    }

    public function getSource(string $name): ?MigrationSource
    {
        return $this->sources[$name] ?? null;
    }

    public function getAvailableSources(): array
    {
        return array_keys($this->sources);
    }

    public function setOutput(OutputStyle $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function getOutput(): ?OutputStyle
    {
        return $this->output;
    }

    public function setConfig(MigrationConfig $config): self
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
                if ($dependency->isOptional() && ! in_array($dependency->entityName, $this->config->excluded)) {
                    $optional[$dependency->entityName] = $dependency;
                }
            }
        }

        return $optional;
    }

    public function migrate(MigrationSource $source): MigrationResult
    {
        if (! $this->config instanceof MigrationConfig) {
            throw new InvalidArgumentException('Migration config not set. Call setConfig() first.');
        }

        if (! $this->output instanceof OutputStyle) {
            throw new InvalidArgumentException('Migration output not set. Call setOutput() first.');
        }

        $result = new MigrationResult;
        $this->migratedEntities = [];

        $this->prepareForMigration($source);

        foreach ($this->config->entities as $entity) {
            $this->migrateEntityWithDependencies($entity, $source, $result);
        }

        return $result;
    }

    public function cleanup(): void
    {
        foreach ($this->sources as $source) {
            $source->cleanup();
        }
    }

    protected function migrateEntityWithDependencies(
        string $entity,
        MigrationSource $source,
        MigrationResult $result,
    ): void {
        if (in_array($entity, $this->migratedEntities)) {
            return;
        }

        if (in_array($entity, $this->config->excluded)) {
            $this->output->writeln("<comment>Skipping $entity - excluded</comment>");
            $this->migratedEntities[] = $entity;

            return;
        }

        $importer = $source->getImporter($entity);

        if (! $importer instanceof Contracts\EntityImporter) {
            throw new InvalidArgumentException("Unknown entity: $entity");
        }

        if ($importer->isCompleted()) {
            $this->output->writeln("<info>Skipping $entity - already completed</info>");
            $this->migratedEntities[] = $entity;

            return;
        }

        $dependencies = $importer->getDependencies();
        $preDependencies = array_filter($dependencies, fn (ImporterDependency $dep): bool => $dep->isPre());

        foreach ($preDependencies as $dependency) {
            if ($dependency->isRequired() || in_array($dependency->entityName, $this->optionalDependencies)) {
                $dependencyType = $dependency->isRequired() ? 'required' : 'optional';
                $this->output->writeln("<comment>Migrating {$dependency->entityName} ({$dependencyType} dependency of {$entity})...</comment>");
                $this->migrateEntityWithDependencies($dependency->entityName, $source, $result);
            }
        }

        $this->migrateEntity($entity, $source, $result);

        if ($this->config->limit === null && $this->config->offset === null && ! $this->config->isDryRun) {
            $importer->markCompleted();
        }

        $this->migratedEntities[] = $entity;

        $postDependencies = array_filter($dependencies, fn (ImporterDependency $dep): bool => $dep->isPost());

        foreach ($postDependencies as $dependency) {
            if ($dependency->isRequired() || in_array($dependency->entityName, $this->optionalDependencies)) {
                $dependencyType = $dependency->isRequired() ? 'required' : 'optional';
                $this->output->writeln("<comment>Migrating {$dependency->entityName} ({$dependencyType} dependency of {$entity})...</comment>");
                $this->migrateEntityWithDependencies($dependency->entityName, $source, $result);
            }
        }
    }

    protected function migrateEntity(
        string $entity,
        MigrationSource $source,
        MigrationResult $result,
    ): void {
        $importer = $source->getImporter($entity);
        $importer->setConfig($this->config);

        if (! $importer instanceof Contracts\EntityImporter) {
            throw new InvalidArgumentException("Unknown entity: $entity");
        }

        $this->output->writeln("<info>Migrating $entity...</info>");

        $importer->import(
            result: $result,
            output: $this->output,
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
