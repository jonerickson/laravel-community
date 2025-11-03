<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Migration\ConcurrentMigrationManager;
use App\Services\Migration\Contracts\EntityImporter;
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
use function Laravel\Prompts\text;

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
                            {--ssh : Connect to the source database via SSH tunnel}
                            {--base-url= : Base URL of the source site for downloading files/images}
                            {--parallel : Enable concurrent processing with multiple processes}
                            {--max-records-per-process=1000 : Maximum records each process should handle before terminating}
                            {--max-processes=4 : Maximum number of concurrent processes to run}
                            {--memory-limit= : Memory limit in MB for worker processes (automatically calculated if not provided)}
                            {--worker : Internal flag indicating this is a worker process (do not use manually)}';

    protected $description = 'Migrate data from external sources (use -v to see skipped/failed records, -vv for migrated records)';

    public function handle(MigrationService $migrationService): int
    {
        $entity = $this->option('entity');
        $batchSize = (int) $this->option('batch');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $offset = $this->option('offset') ? (int) $this->option('offset') : null;
        $isDryRun = (bool) $this->option('dry-run');

        $source = $this->getOrSelectSource($migrationService);
        $sourceInstance = $this->getSourceInstance($migrationService, $source);

        if (! $sourceInstance instanceof MigrationSource) {
            return self::FAILURE;
        }

        $this->setupBaseUrlIfNeeded($sourceInstance);

        if ($this->option('worker')) {
            if (in_array($source, [null, '', '0'], true) || ! $entity) {
                $this->error('Worker mode requires --source and --entity');

                return self::FAILURE;
            }

            return $this->handleWorkerMode(
                migrationService: $migrationService,
                source: $sourceInstance,
                entity: $entity,
                batchSize: $batchSize,
                limit: $limit,
                offset: $offset,
                isDryRun: $isDryRun
            );
        }

        return $this->handleCoordinatorMode(
            migrationService: $migrationService,
            source: $sourceInstance,
            entity: $entity,
            batchSize: $batchSize,
            limit: $limit,
            offset: $offset,
            isDryRun: $isDryRun
        );
    }

    protected function handleWorkerMode(
        MigrationService $migrationService,
        MigrationSource $source,
        string $entity,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
    ): int {
        $memoryLimit = $this->option('memory-limit') ?: 1024;
        $memoryLimitBytes = $memoryLimit * 1024 * 1024;
        $memoryThresholdBytes = (int) ($memoryLimitBytes * 0.9);

        ini_set('memory_limit', $memoryLimit.'M');

        $startMemory = memory_get_usage(true);
        $this->info('Starting - Memory: '.round($startMemory / 1024 / 1024, 2).'MB (Limit: '.$memoryLimit.'MB, Threshold: '.round($memoryThresholdBytes / 1024 / 1024, 2).'MB)');

        $checkMemory = function () use ($memoryThresholdBytes, $memoryLimit): void {
            $currentMemory = memory_get_usage(true);

            if ($currentMemory >= $memoryThresholdBytes) {
                $usedMB = round($currentMemory / 1024 / 1024, 2);
                $this->warn("Memory threshold reached: {$usedMB}MB / {$memoryLimit}MB. Exiting gracefully to prevent out-of-memory error.");

                DB::disconnect();
                gc_collect_cycles();

                exit(self::SUCCESS);
            }
        };

        register_tick_function($checkMemory);

        $sshTunnel = null;

        try {
            $sshTunnel = $this->setupSshTunnelIfNeeded($source);

            if ($this->option('ssh') && $sshTunnel === null) {
                unregister_tick_function($checkMemory);

                return self::FAILURE;
            }

            $this->info("Processing $entity: Batch Size $batchSize, Offset $offset, Limit $limit");

            declare(ticks=100) {
                $result = $migrationService->migrate(
                    source: $source->getName(),
                    entity: $entity,
                    batchSize: $batchSize,
                    limit: $limit,
                    offset: $offset,
                    isDryRun: $isDryRun,
                    output: $this->output,
                );
            }

            unregister_tick_function($checkMemory);

            DB::disconnect($source->getConnection());
            gc_collect_cycles();

            $stats = $result->entities[$entity] ?? ['migrated' => 0, 'skipped' => 0, 'failed' => 0];
            $endMemory = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);

            $this->info("Completed: Migrated {$stats['migrated']}, Skipped {$stats['skipped']}, Failed {$stats['failed']}");
            $this->info('Memory - Current: '.round($endMemory / 1024 / 1024, 2).'MB, Peak: '.round($peakMemory / 1024 / 1024, 2).'MB');

            return self::SUCCESS;
        } catch (Exception $e) {
            unregister_tick_function($checkMemory);
            $this->error("Migration failed: {$e->getMessage()}");

            return self::FAILURE;
        } finally {
            if ($sshTunnel !== null && $sshTunnel !== []) {
                $this->closeSshTunnel($sshTunnel);
            }
        }
    }

    protected function handleCoordinatorMode(
        MigrationService $migrationService,
        MigrationSource $source,
        string $entity,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
    ): int {
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
            $sshTunnel = $this->setupSshTunnelIfNeeded($source);

            if ($this->option('ssh') && $sshTunnel === null) {
                return self::FAILURE;
            }

            if ($this->option('check')) {
                return $this->checkDatabaseConnection($source);
            }

            if ($this->option('status')) {
                return $this->displayMigrationStatus($source, $entity);
            }

            if (! $this->confirmToProceed()) {
                return self::SUCCESS;
            }

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

            $this->info("Starting migration from {$source->getConnection()}...");

            if ($this->option('parallel') && $entity) {
                return $this->runConcurrentMigration(
                    source: $source,
                    entity: $entity,
                    batchSize: $batchSize,
                    limit: $limit,
                    offset: $offset,
                    isDryRun: $isDryRun,
                    migrationService: $migrationService,
                );
            }

            $result = $migrationService->migrate(
                source: $source->getName(),
                entity: $entity,
                batchSize: $batchSize,
                limit: $limit,
                offset: $offset,
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

            $migrationService->cleanup();
            $result->cleanup();

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

    protected function runConcurrentMigration(
        MigrationSource $source,
        string $entity,
        int $batchSize,
        ?int $limit,
        ?int $offset,
        bool $isDryRun,
        MigrationService $migrationService,
    ): int {
        $maxRecordsPerProcess = (int) $this->option('max-records-per-process');
        $maxProcesses = (int) $this->option('max-processes');

        $workerMemoryLimit = $this->calculateWorkerMemoryLimit($maxProcesses);

        if ($workerMemoryLimit === null) {
            return self::FAILURE;
        }

        $this->info("Worker memory limit calculated: {$workerMemoryLimit}MB per process");

        $totalRecords = DB::connection($source->getConnection())
            ->table($migrationService->getImporterForEntity($entity)?->getSourceTable() ?? '')
            ->count();

        if ($limit !== null && $limit !== 0) {
            $totalRecords = min($totalRecords, $limit + ($offset ?? 0));
        }

        $manager = new ConcurrentMigrationManager(
            maxRecordsPerProcess: $maxRecordsPerProcess,
            maxConcurrentProcesses: $maxProcesses,
            output: $this->output,
            workerMemoryLimit: $workerMemoryLimit,
        );

        $this->trap([SIGINT, SIGTERM], function () use ($manager, $migrationService): void {
            $this->warn('Concurrent migration interrupted. Terminating processes...');
            $manager->terminateAll();
            $migrationService->cleanup();
            exit(1);
        });

        $result = $manager->migrate(
            source: $source,
            entity: $entity,
            totalRecords: $totalRecords,
            batchSize: $batchSize,
            isDryRun: $isDryRun,
            useSsh: (bool) $this->option('ssh'),
            globalOffset: $offset,
        );

        $migrationService->cleanup();

        if ($result) {
            $source->getImporter($entity)->markCompleted();
        }

        return self::SUCCESS;
    }

    protected function promptForOptionalDependencies(MigrationService $migrationService, MigrationSource $source, ?string $entity): void
    {
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
    protected function findAvailablePort(int $minPort = 10000, int $maxPort = 65000, int $maxAttempts = 100): ?int
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $port = random_int($minPort, $maxPort);

            $result = Process::run("lsof -ti:$port");

            if ($result->failed() || trim($result->output()) === '') {
                return $port;
            }
        }

        return null;
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

        $localPort = $this->findAvailablePort();

        if ($localPort === null) {
            $this->error('Could not find an available port for SSH tunnel.');

            return null;
        }

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

    protected function displayMigrationStatus(MigrationSource $source, ?string $entity = null): int
    {
        $this->info("Fetching migration status from {$source->getName()}...");
        $this->newLine();

        $connection = $source->getConnection();
        $importers = array_filter($source->getImporters(), fn (EntityImporter $importer): bool => is_null($entity) || $importer->getEntityName() === $entity);

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

        usort($statusData, fn (array $a, array $b): int => strcmp($a['entity'], $b['entity']));

        $this->table(
            ['Entity', 'Source Table', 'Record Count'],
            $statusData
        );

        return self::SUCCESS;
    }

    protected function getOrSelectSource(MigrationService $migrationService): ?string
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

            return null;
        }

        return $source;
    }

    protected function getSourceInstance(MigrationService $migrationService, string $source): ?MigrationSource
    {
        $sourceInstance = $migrationService->getSource($source);

        if (! $sourceInstance instanceof MigrationSource) {
            $this->error('Invalid migration source.');

            return null;
        }

        return $sourceInstance;
    }

    /**
     * @throws RandomException
     */
    protected function setupSshTunnelIfNeeded(MigrationSource $sourceInstance): ?array
    {
        if (! $this->option('ssh')) {
            return [];
        }

        $sshConfig = $sourceInstance->getSshConfig();

        if ($sshConfig === null || $sshConfig === []) {
            $this->error('SSH configuration not found for this source.');
            $this->warn('Please configure SSH credentials in your .env file:');
            $this->warn('MIGRATION_IC_SSH_HOST, MIGRATION_IC_SSH_USER, MIGRATION_IC_SSH_PORT, MIGRATION_IC_SSH_KEY');

            return null;
        }

        $this->info('Creating SSH tunnel...');
        $sshTunnel = $this->createSshTunnel($sshConfig, $sourceInstance->getConnection());

        if ($sshTunnel === null || $sshTunnel === []) {
            $this->error('Failed to create SSH tunnel.');

            return null;
        }

        $this->info('SSH tunnel established successfully.');

        return $sshTunnel;
    }

    protected function setupBaseUrlIfNeeded(MigrationSource $source): void
    {
        $baseUrl = $this->option('base-url') ?? $source->getBaseUrl();

        if ($baseUrl === null || $baseUrl === '') {
            $baseUrl = text(
                label: 'Enter the base URL of the source site (for downloading files/images)',
                placeholder: 'https://example.com',
                hint: 'Leave empty to skip file downloads',
            );
        }

        if ($baseUrl !== '') {
            $source->setBaseUrl($baseUrl);
            $this->info("Base URL configured: $baseUrl");
        }
    }

    protected function calculateWorkerMemoryLimit(int $maxProcesses): ?int
    {
        $totalMemoryMB = $this->getTotalSystemMemory();

        if ($totalMemoryMB === null) {
            $this->error('Unable to determine system memory. Cannot calculate worker memory limit.');

            return null;
        }

        $this->info("System Memory: {$totalMemoryMB}MB");

        $osReservedMemoryMB = (int) ($totalMemoryMB * 0.25);
        $availableMemoryMB = $totalMemoryMB - $osReservedMemoryMB;

        $workerMemoryLimitMB = (int) floor($availableMemoryMB / $maxProcesses);

        $memoryAfterAllocation = $totalMemoryMB - ($workerMemoryLimitMB * $maxProcesses);
        $memoryAfterAllocationPercent = ($memoryAfterAllocation / $totalMemoryMB) * 100;

        if ($memoryAfterAllocationPercent < 25) {
            $this->error('Insufficient memory available for the requested number of processes.');
            $this->error("Total System Memory: {$totalMemoryMB}MB");
            $this->error("Max Processes: {$maxProcesses}");
            $this->error("Memory per Process: {$workerMemoryLimitMB}MB");
            $this->error("Memory Left for OS: {$memoryAfterAllocation}MB ({$memoryAfterAllocationPercent}%)");
            $this->error('At least 25% of total memory must remain available for the OS.');

            $maxSafeProcesses = (int) floor($availableMemoryMB / 256);
            $this->warn("Consider reducing --max-processes to {$maxSafeProcesses} or fewer.");

            return null;
        }

        $this->info("Memory allocation: {$workerMemoryLimitMB}MB per process, {$memoryAfterAllocation}MB ({$memoryAfterAllocationPercent}%) reserved for OS");

        return $workerMemoryLimitMB;
    }

    protected function getTotalSystemMemory(): ?int
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            $result = Process::run('sysctl -n hw.memsize');

            if ($result->successful()) {
                return (int) round((int) trim($result->output()) / 1024 / 1024);
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $result = Process::run('grep MemTotal /proc/meminfo | awk \'{print $2}\'');

            if ($result->successful()) {
                return (int) round((int) trim($result->output()) / 1024);
            }
        }

        return null;
    }
}
