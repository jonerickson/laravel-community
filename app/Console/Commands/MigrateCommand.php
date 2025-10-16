<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Migration\Contracts\MigrationSource;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\MigrationService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class MigrateCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'mi:migrate
                            {source? : The migration source (e.g., invision-community)}
                            {--force : Force the operation to run when in production}
                            {--entity= : Specific entity to migrate (e.g., users, posts)}
                            {--batch-size=100 : Number of records to process per batch}
                            {--limit= : Maximum number of records to migrate (useful for testing)}
                            {--dry-run : Preview migration without making changes}';

    protected $description = 'Migrate data from external sources (use -v to see skipped/failed records, -vv for migrated records)';

    public function handle(MigrationService $migrationService): int
    {
        if (! $this->confirmToProceed()) {
            return self::SUCCESS;
        }

        $source = $this->argument('source');

        if (! $source) {
            $source = select(
                label: 'Select migration source',
                options: $migrationService->getAvailableSources(),
            );
        }

        $entity = $this->option('entity');
        $batchSize = (int) $this->option('batch-size');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('Running in DRY RUN mode - no changes will be made.');
        }

        if ($limit !== null && $limit !== 0) {
            $this->warn("Limiting migration to $limit records.");
        }

        if (! in_array($source, $migrationService->getAvailableSources())) {
            $this->error("Unknown migration source: $source");

            return self::FAILURE;
        }

        $this->promptForOptionalDependencies($migrationService, $source, $entity);

        try {
            $this->info("Starting migration from $source...");

            $result = $migrationService->migrate(
                source: $source,
                entity: $entity,
                batchSize: $batchSize,
                limit: $limit,
                isDryRun: $isDryRun,
                output: $this->output,
            );

            $this->newLine();
            $this->info('Migration completed successfully!');
            $this->table(
                ['Entity', 'Migrated', 'Skipped', 'Failed'],
                $result->toTableRows(),
            );

            $this->displayVerboseOutput($result);

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Migration failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function promptForOptionalDependencies(MigrationService $migrationService, string $sourceName, ?string $entity): void
    {
        $source = $migrationService->getSource($sourceName);

        if (! $source instanceof MigrationSource) {
            return;
        }

        $optionalDependencies = $migrationService->getOptionalDependencies($source, $entity);

        if ($optionalDependencies === []) {
            return;
        }

        $this->newLine();
        $this->info('Optional dependencies detected:');

        $options = [];

        foreach ($optionalDependencies as $dependency) {
            $label = $dependency->entityName;

            if ($dependency->description) {
                $label .= " - {$dependency->description}";
            }

            $options[$dependency->entityName] = $label;
        }

        $selected = multiselect(
            label: 'Select optional dependencies to include',
            options: $options,
        );

        $migrationService->setOptionalDependencies($selected);
    }

    protected function displayVerboseOutput(MigrationResult $result): void
    {
        if (! $this->output->isVerbose()) {
            return;
        }

        $this->newLine();

        foreach ($result->entities as $entity => $stats) {
            if ($stats['skipped'] > 0) {
                $this->newLine();
                $this->warn("Skipped $entity:");
                $skippedRecords = $result->getSkippedRecords($entity);

                if ($skippedRecords !== []) {
                    $this->table(
                        array_keys($skippedRecords[0]),
                        array_map(fn ($record) => array_values($record), $skippedRecords),
                    );
                }
            }

            if ($stats['failed'] > 0) {
                $this->newLine();
                $this->error("Failed $entity:");
                $failedRecords = $result->getFailedRecords($entity);

                if ($failedRecords !== []) {
                    $this->table(
                        array_keys($failedRecords[0]),
                        array_map(fn ($record) => array_values($record), $failedRecords),
                    );
                }
            }

            if ($this->output->isVeryVerbose() && $stats['migrated'] > 0) {
                $this->newLine();
                $this->info("Migrated $entity:");
                $migratedRecords = $result->getMigratedRecords($entity);

                if ($migratedRecords !== []) {
                    $this->table(
                        array_keys($migratedRecords[0]),
                        array_map(fn ($record) => array_values($record), $migratedRecords),
                    );
                }
            }
        }
    }
}
