<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Migration\Contracts\MigrationSource;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\MigrationService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Random\RandomException;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class MigrateCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'mi:migrate
                            {source? : The migration source (e.g., invision-community)}
                            {--force : Force the operation to run when in production}
                            {--entity= : Specific entity to migrate (e.g., users, posts)}
                            {--batch=1000 : Number of records to process per batch}
                            {--limit= : Maximum number of records to migrate (useful for testing)}
                            {--offset= : Number of records to skip before starting migration (useful for resuming)}
                            {--dry-run : Preview migration without making changes}
                            {--check : Verify database connection and exit}
                            {--status : Display migration status with record counts for each entity}
                            {--ssh : Connect to the source database via SSH tunnel}';

    protected $description = 'Migrate data from external sources (use -v to see skipped/failed records, -vv for migrated records)';

    public function handle(MigrationService $migrationService): int
    {
        $source = $this->argument('source');

        if (! $source) {
            $source = select(
                label: 'Select migration source',
                options: $migrationService->getAvailableSources(),
            );
        }

        if (! in_array($source, $migrationService->getAvailableSources())) {
            $this->error("Unknown migration source: $source");

            return self::FAILURE;
        }

        $sourceInstance = $migrationService->getSource($source);

        if (! $sourceInstance instanceof MigrationSource) {
            $this->error('Invalid migration source.');

            return self::FAILURE;
        }

        $sshTunnel = null;

        $this->trap([SIGINT, SIGTERM], function () use (&$sshTunnel, $migrationService): void {
            $this->warn('Migration interrupted. Cleaning up...');

            $migrationService->cleanup();

            if ($sshTunnel !== null && $sshTunnel !== []) {
                $this->closeSshTunnel($sshTunnel);
            }

            exit(1);
        });

        try {
            if ($this->option('ssh')) {
                $sshConfig = $sourceInstance->getSshConfig();

                if ($sshConfig === null || $sshConfig === []) {
                    $this->error('SSH configuration not found for this source.');
                    $this->warn('Please configure SSH credentials in your .env file:');
                    $this->warn('MIGRATION_IC_SSH_HOST, MIGRATION_IC_SSH_USER, MIGRATION_IC_SSH_PORT, MIGRATION_IC_SSH_KEY');

                    return self::FAILURE;
                }

                $this->info('Creating SSH tunnel...');
                $sshTunnel = $this->createSshTunnel($sshConfig, $sourceInstance->getConnection());

                if ($sshTunnel === null || $sshTunnel === []) {
                    $this->error('Failed to create SSH tunnel.');

                    return self::FAILURE;
                }

                $this->info('SSH tunnel established successfully.');
            }

            if ($this->option('check')) {
                return $this->checkDatabaseConnection($sourceInstance);
            }

            if ($this->option('status')) {
                return $this->displayMigrationStatus($migrationService, $source, $sourceInstance);
            }

            if (! $this->confirmToProceed()) {
                return self::SUCCESS;
            }

            $entity = $this->option('entity');
            $batchSize = (int) $this->option('batch');
            $limit = $this->option('limit') ? (int) $this->option('limit') : null;
            $offset = $this->option('offset') ? (int) $this->option('offset') : null;
            $isDryRun = (bool) $this->option('dry-run');

            if ($isDryRun) {
                $this->warn('Running in DRY RUN mode - no changes will be made.');
            }

            if ($limit !== null && $limit !== 0) {
                $this->warn("Limiting migration to $limit records.");
            }

            if ($offset !== null && $offset !== 0) {
                $this->warn("Starting migration from offset $offset (skipping first $offset records).");
            }

            $this->promptForOptionalDependencies($migrationService, $source, $entity);

            $this->info("Starting migration from $source...");

            $result = $migrationService->migrate(
                source: $source,
                entity: $entity,
                batchSize: $batchSize,
                limit: $limit,
                offset: $offset,
                isDryRun: $isDryRun,
                output: $this->output,
            );

            $migrationService->cleanup();
            $result->cleanup();

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
        } finally {
            if ($sshTunnel !== null && $sshTunnel !== []) {
                $this->closeSshTunnel($sshTunnel);
            }
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

    protected function checkDatabaseConnection(MigrationSource $source): int
    {
        $this->info('Checking database connection...');

        try {
            $connection = $source->getConnection();
            DB::connection($connection)->getPdo();

            $databaseName = DB::connection($connection)->getDatabaseName();
            $driver = DB::connection($connection)->getDriverName();

            $this->info("Successfully connected to database: $databaseName (Driver: $driver)");

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Failed to connect to database.');
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * @throws RandomException
     */
    protected function createSshTunnel(array $sshConfig, string $connectionName): ?array
    {
        $dbConfig = config("database.connections.$connectionName");

        if (! $dbConfig) {
            return null;
        }

        $localPort = random_int(10000, 65000);
        $remoteHost = $dbConfig['host'];
        $remotePort = $dbConfig['port'];

        $sshCommand = sprintf(
            'ssh -f -N -L %d:%s:%d -p %d -o ServerAliveInterval=60 -o ServerAliveCountMax=3 %s@%s',
            $localPort,
            $remoteHost,
            $remotePort,
            $sshConfig['port'],
            $sshConfig['user'],
            $sshConfig['host']
        );

        if ($sshConfig['key']) {
            $sshCommand .= " -i {$sshConfig['key']}";
        }

        $result = Process::timeout(10)->run($sshCommand);

        if ($result->failed()) {
            $this->error('SSH tunnel creation failed: '.$result->errorOutput());

            if ($result->output()) {
                $this->error('Command output: '.$result->output());
            }

            return null;
        }

        Sleep::for(1)->second();

        config(["database.connections.$connectionName.host" => '127.0.0.1']);
        config(["database.connections.$connectionName.port" => $localPort]);

        DB::purge($connectionName);

        return [
            'local_port' => $localPort,
            'remote_host' => $remoteHost,
            'remote_port' => $remotePort,
            'ssh_config' => $sshConfig,
        ];
    }

    protected function closeSshTunnel(array $sshTunnel): void
    {
        $localPort = $sshTunnel['local_port'];

        Process::pipe([
            "lsof -ti:$localPort",
            'xargs kill -9',
        ]);

        $this->info('SSH tunnel closed.');
    }

    protected function displayMigrationStatus(MigrationService $migrationService, string $sourceName, MigrationSource $source): int
    {
        $this->info("Fetching migration status from $sourceName...");
        $this->newLine();

        $connection = $source->getConnection();
        $importers = $source->getImporters();

        $statusData = [];

        foreach ($importers as $entityName => $importer) {
            try {
                $sourceTable = $importer->getSourceTable();
                $count = DB::connection($connection)
                    ->table($sourceTable)
                    ->count();

                $statusData[] = [
                    'entity' => $entityName,
                    'source_table' => $sourceTable,
                    'record_count' => number_format($count),
                ];
            } catch (Exception $e) {
                $statusData[] = [
                    'entity' => $entityName,
                    'source_table' => $importer->getSourceTable(),
                    'record_count' => "Error: {$e->getMessage()}",
                ];
            }
        }

        usort($statusData, fn (array $a, array $b): int => strcmp((string) $a['entity'], (string) $b['entity']));

        $this->table(
            ['Entity', 'Source Table', 'Record Count'],
            $statusData
        );

        return self::SUCCESS;
    }
}
