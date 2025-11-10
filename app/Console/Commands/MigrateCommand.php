<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Migration\ConcurrentMigrationManager;
use App\Services\Migration\Contracts\EntityImporter;
use App\Services\Migration\Contracts\MigrationSource;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use App\Services\Migration\MigrationService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Cache;
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

    protected $signature = 'app:migrate
                            {source? : The migration source}
                            {--force : Force the operation to run when in production}
                            {--entity= : Specific entity or comma-delimited list of entities to migrate}
                            {--batch=1000 : Number of records to process per batch}
                            {--limit= : Maximum number of records to migrate}
                            {--id= : A specific user ID to only import}
                            {--offset= : Number of records to skip before starting migration}
                            {--dry-run : Preview migration without making changes}
                            {--check : Verify database connection and exit}
                            {--status : Display migration status with record counts for each entity}
                            {--cleanup=1 : Cleanup the migration after it has finished}
                            {--ssh : Connect to the source database via SSH tunnel}
                            {--media=1 : Download and store media files}
                            {--base-url= : Base URL of the source site for downloading files/images}
                            {--excluded= : Comma-delimited list of entities to exclude from migration}
                            {--parallel : Enable concurrent processing with multiple processes}
                            {--max-records-per-process=1000 : Maximum records each process should handle before terminating}
                            {--max-processes=4 : Maximum number of concurrent processes to run}
                            {--memory-limit= : Memory limit in MB for worker processes (automatically calculated if not provided)}
                            {--worker : Internal flag indicating this is a worker process (do not use manually)}';

    protected $description = 'Migrate data from external sources (use -v to see skipped/failed records, -vv for migrated records)';

    public function handle(MigrationService $service): int
    {
        if ($this->runChecks() === self::FAILURE) {
            return self::FAILURE;
        }

        $source = $this->getOrSelectSource($service);
        $sourceInstance = $this->getSourceInstance($service, $source);

        if (! $sourceInstance instanceof MigrationSource) {
            return self::FAILURE;
        }

        $this->setupBaseUrlIfNeeded($sourceInstance);

        $config = $this->buildMigrationConfig();
        $service->configure($config);

        if ($this->option('worker')) {
            if (in_array($source, [null, '', '0'], true) || in_array($config->entity, [null, '', '0'], true)) {
                $this->error('Worker mode requires --source and --entity');

                return self::FAILURE;
            }

            return $this->handleWorkerMode(
                service: $service,
                source: $sourceInstance,
            );
        }

        return $this->handleCoordinatorMode(
            service: $service,
            source: $sourceInstance,
        );
    }

    protected function runChecks(): int
    {
        if (! Cache::supportsTags()) {
            $this->error('The current cache driver does not support tagging.');
            $this->error('Please configure a cache driver that supports tagging (redis, memcached, or dynamodb).');
            $this->info('Current cache driver: '.config('cache.default'));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function handleWorkerMode(
        MigrationService $service,
        MigrationSource $source,
    ): int {
        $config = $service->getConfig();

        $memoryLimit = $config->memoryLimit ?: 1024;
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

            if ($config->useSsh && $sshTunnel === null) {
                unregister_tick_function($checkMemory);

                return self::FAILURE;
            }

            $this->info("Processing {$config->entity}: Batch Size {$config->batchSize}, Offset {$config->offset}, Limit {$config->limit}");

            declare(ticks=100) {
                $result = $service->migrate(
                    source: $source->getName(),
                    output: $this->output,
                );
            }

            unregister_tick_function($checkMemory);

            DB::disconnect($source->getConnection());
            gc_collect_cycles();

            $stats = $result->entities[$config->entity] ?? ['migrated' => 0, 'skipped' => 0, 'failed' => 0];
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
        MigrationService $service,
        MigrationSource $source,
    ): int {
        $config = $service->getConfig();
        $sshTunnel = null;

        $this->trap([SIGINT, SIGTERM], function () use (&$sshTunnel, $service): void {
            $this->warn('Migration interrupted...');

            if ($this->option('cleanup')) {
                $this->warn('Cleaning up...');
                $service->cleanup();
            }

            if ($sshTunnel !== null && $sshTunnel !== []) {
                $this->closeSshTunnel($sshTunnel);
            }

            exit(1);
        });

        try {
            $sshTunnel = $this->setupSshTunnelIfNeeded($source);

            if ($config->useSsh && $sshTunnel === null) {
                return self::FAILURE;
            }

            if ($this->option('check')) {
                return $this->checkDatabaseConnection($source);
            }

            if ($this->option('status')) {
                return $this->displayMigrationStatus($source, $config->entity);
            }

            if (! $this->confirmToProceed()) {
                return self::SUCCESS;
            }

            if ($config->isDryRun) {
                $this->warn('Running in DRY RUN mode - no changes will be made.');
            }

            if ($config->limit !== null && $config->limit !== 0) {
                $this->warn("Limiting migration to {$config->limit} records.");
            }

            if ($config->offset !== null && $config->offset !== 0) {
                $this->warn("Starting migration from offset {$config->offset} (skipping first {$config->offset} records).");
            }

            if ($config->excluded !== []) {
                $this->warn('Excluding entities: '.implode(', ', $config->excluded));
            }

            $this->promptForOptionalDependencies($service, $source, $config->entity);

            $this->info("Starting migration from {$source->getConnection()}...");

            if ($config->parallel && $config->entity) {
                return $this->runConcurrentMigration(
                    source: $source,
                    service: $service,
                );
            }

            $result = $service->migrate(
                source: $source->getName(),
                output: $this->output,
            );

            $this->newLine();
            $this->info('Migration completed successfully!');
            $this->table(
                ['Entity', 'Migrated', 'Skipped', 'Failed'],
                $result->toTableRows(),
            );

            $this->displayVerboseOutput($result);

            if ($this->option('cleanup')) {
                $this->warn('Cleaning up...');
                $service->cleanup();
                $result->cleanup();
            }

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
        MigrationService $service,
    ): int {
        $config = $service->getConfig();

        $workerMemoryLimit = $this->calculateWorkerMemoryLimit($config->maxProcesses);

        if ($workerMemoryLimit === null) {
            return self::FAILURE;
        }

        $this->info("Worker memory limit calculated: {$workerMemoryLimit}MB per process");

        $totalRecords = DB::connection($source->getConnection())
            ->table($service->getImporterForEntity($config->entity)?->getSourceTable() ?? '')
            ->count();

        if ($config->limit !== null && $config->limit !== 0) {
            $totalRecords = min($totalRecords, $config->limit + ($config->offset ?? 0));
        }

        $manager = new ConcurrentMigrationManager(
            config: $service->getConfig(),
            output: $this->output,
            workerMemoryLimit: $workerMemoryLimit,
        );

        $this->trap([SIGINT, SIGTERM], function () use ($manager, $service): void {
            $this->warn('Concurrent migration interrupted. Terminating processes...');
            $manager->terminateAll();

            if ($this->option('cleanup')) {
                $this->warn('Cleaning up...');
                $service->cleanup();
            }

            exit(1);
        });

        $result = $manager->migrate(
            source: $source,
            totalRecords: $totalRecords,
        );

        if ($this->option('cleanup')) {
            $this->warn('Cleaning up...');
            $service->cleanup();
        }

        if ($result) {
            $source->getImporter($config->entity)->markCompleted();
        }

        return self::SUCCESS;
    }

    protected function promptForOptionalDependencies(MigrationService $service, MigrationSource $source, ?string $entity): void
    {
        $optionalDependencies = $service->getOptionalDependencies($source, $entity);

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

        $service->setOptionalDependencies($selected);
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

        if (file_exists($sshConfig['key'])) {
            $sshCommand .= " -i {$sshConfig['key']}";
        } else {
            $sshCommand .= " -i /dev/stdin <<< '{$sshConfig['key']}'";
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

    protected function getOrSelectSource(MigrationService $service): ?string
    {
        $source = $this->argument('source');

        if (! $source) {
            $source = select(
                label: 'Select migration source',
                options: $service->getAvailableSources(),
            );
        }

        if (! in_array($source, $service->getAvailableSources())) {
            $this->error("Unknown migration source: $source");

            return null;
        }

        return $source;
    }

    protected function getSourceInstance(MigrationService $service, string $source): ?MigrationSource
    {
        $sourceInstance = $service->getSource($source);

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

    protected function buildMigrationConfig(): MigrationConfig
    {
        $excluded = [];
        $entities = [];
        $entity = $this->option('entity');

        if ($this->option('excluded')) {
            $excluded = array_map('trim', explode(',', $this->option('excluded')));
        }

        if ($entity) {
            $entities = array_map('trim', explode(',', $entity));

            if (count($entities) === 1) {
                $entity = $entities[0];
                $entities = [];
            } else {
                $entity = null;
            }
        }

        return new MigrationConfig(
            entity: $entity,
            entities: $entities,
            batchSize: (int) $this->option('batch'),
            limit: $this->option('limit') ? (int) $this->option('limit') : null,
            offset: $this->option('offset') ? (int) $this->option('offset') : null,
            userId: $this->option('id') ? (int) $this->option('id') : null,
            isDryRun: (bool) $this->option('dry-run'),
            useSsh: (bool) $this->option('ssh'),
            downloadMedia: (bool) $this->option('media'),
            baseUrl: $this->option('base-url'),
            parallel: (bool) $this->option('parallel'),
            maxRecordsPerProcess: (int) $this->option('max-records-per-process'),
            maxProcesses: (int) $this->option('max-processes'),
            memoryLimit: $this->option('memory-limit') ? (int) $this->option('memory-limit') : null,
            excluded: $excluded,
        );
    }
}
