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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Number;
use Illuminate\Support\Sleep;
use Random\RandomException;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
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
                            {--max-processes=8 : Maximum number of concurrent processes to run}
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

        $config = $this->buildMigrationConfig($sourceInstance);
        $service->setConfig($config);
        $service->setOutput($this->output);
        $service->setComponents($this->components);

        if ($this->option('worker')) {
            if (in_array($source, [null, '', '0'], true) || $config->entities === [] || count($config->entities) !== 1) {
                $this->components->error('Worker mode requires --source and a single --entity');

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
            $this->components->error('The current cache driver does not support tagging.');
            $this->components->error('Please configure a cache driver that supports tagging (redis, memcached, or dynamodb).');
            $this->components->info('Current cache driver: '.config('cache.default'));

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
        $this->components->info('Starting - Memory: '.round($startMemory / 1024 / 1024, 2).'MB (Limit: '.$memoryLimit.'MB, Threshold: '.round($memoryThresholdBytes / 1024 / 1024, 2).'MB)');

        $checkMemory = function () use ($memoryThresholdBytes, $memoryLimit): void {
            $currentMemory = memory_get_usage(true);

            if ($currentMemory >= $memoryThresholdBytes) {
                $usedMB = round($currentMemory / 1024 / 1024, 2);
                $this->components->warn(sprintf('Memory threshold reached: %sMB / %sMB. Exiting gracefully to prevent out-of-memory error.', $usedMB, $memoryLimit));

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

            $entity = $config->entities[0] ?? 'unknown';
            $offset = $config->offset ?? 0;
            $this->components->info(sprintf('Processing %s: Batch Size %d, Offset %d, Limit %s', $entity, $config->batchSize, $offset, $config->limit));

            declare(ticks=100) {
                $result = $service->migrate(
                    source: $source,
                );
            }

            unregister_tick_function($checkMemory);

            DB::disconnect($source->getConnection());
            gc_collect_cycles();

            $stats = $result->entities[$entity] ?? ['migrated' => 0, 'skipped' => 0, 'failed' => 0];
            $endMemory = memory_get_usage(true);
            $peakMemory = memory_get_peak_usage(true);

            $this->components->success(sprintf('Completed: Migrated %s, Skipped %s, Failed %s', $stats['migrated'], $stats['skipped'], $stats['failed']));
            $this->components->info('Ending - Memory: '.round($endMemory / 1024 / 1024, 2).'MB, Peak: '.round($peakMemory / 1024 / 1024, 2).'MB');

            $this->displayResults($result);
            $this->displayVerboseResults($result);

            return self::SUCCESS;
        } catch (Exception $exception) {
            unregister_tick_function($checkMemory);
            $this->components->error('Migration failed: '.$exception->getMessage());

            Log::error('Migration failed: '.$exception->getMessage());

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
            $this->output->newLine(2);
            $this->components->warn('Migration interrupted...');

            if ($this->option('cleanup')) {
                $this->components->warn('Cleaning up...');
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

            if ($this->checkDatabaseConnection($source) === self::FAILURE) {
                return self::FAILURE;
            }

            if ($this->option('status')) {
                $entity = count($config->entities) === 1 ? $config->entities[0] : null;

                return $this->displayMigrationStatus($source, $entity);
            }

            if (! $this->confirmToProceed()) {
                return self::SUCCESS;
            }

            if ($config->isDryRun) {
                $this->components->warn('Running in DRY RUN mode - no changes will be made.');
            }

            if ($config->limit !== null && $config->limit !== 0) {
                $this->components->warn(sprintf('Limiting migration to %s records.', $config->limit));
            }

            if ($config->offset !== null && $config->offset !== 0) {
                $this->components->warn(sprintf('Starting migration from offset %s.', $config->offset));
            }

            if ($config->excluded !== []) {
                $this->components->warn('Excluding entities: '.implode(', ', $config->excluded));
            }

            $entity = count($config->entities) === 1 ? $config->entities[0] : null;
            $this->promptForOptionalDependencies($service, $source, $entity);

            $this->components->info(sprintf('Starting migration from %s...', $source->getConnection()));

            if ($config->parallel) {
                return $this->runConcurrentMigrationsForMultipleEntities(
                    source: $source,
                    service: $service,
                );
            }

            $result = $service->migrate(
                source: $source,
            );

            $this->components->success('Migration completed successfully!');

            $this->displayResults($result);
            $this->displayVerboseResults($result);

            if ($this->option('cleanup')) {
                $this->components->warn('Cleaning up...');
                $service->cleanup();
                $result->cleanup();
            }

            return self::SUCCESS;
        } catch (Exception $exception) {
            $this->components->error('Migration failed: '.$exception->getMessage());

            Log::error('Failed to migrate '.$source->getName(), [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return self::FAILURE;
        } finally {
            if ($sshTunnel !== null && $sshTunnel !== []) {
                $this->closeSshTunnel($sshTunnel);
            }
        }
    }

    protected function runConcurrentMigration(
        string $entity,
        MigrationSource $source,
        MigrationService $service,
    ): int {
        $config = $service->getConfig();

        $workerMemoryLimit = $this->calculateWorkerMemoryLimit($config->maxProcesses);

        if ($workerMemoryLimit === null) {
            return self::FAILURE;
        }

        $importer = $source->getImporter($entity);
        $importer->setConfig($config);

        $totalRecords = $importer->getTotalRecordsCount();

        if ($config->limit !== null && $config->limit !== 0) {
            $totalRecords = min($totalRecords, $config->limit + ($config->offset ?? 0));
        }

        $manager = new ConcurrentMigrationManager(
            entity: $entity,
            config: $service->getConfig(),
            output: $this->output,
            components: $this->components,
            workerMemoryLimit: $workerMemoryLimit,
        );

        $this->trap([SIGINT, SIGTERM], function () use ($manager, $service): void {
            $this->output->newLine(2);
            $this->components->warn('Concurrent migration interrupted. Terminating processes...');
            $manager->terminateAll();

            if ($this->option('cleanup')) {
                $this->components->warn('Cleaning up...');
                $service->cleanup();
            }

            exit(1);
        });

        $result = $manager->migrate(
            source: $source,
            totalRecords: $totalRecords,
        );

        $this->components->success('Migration completed successfully!');

        if ($this->option('cleanup')) {
            $this->components->warn('Cleaning up...');
            $service->cleanup();
        }

        if ($result) {
            $source->getImporter($entity)->markCompleted();
        }

        return self::SUCCESS;
    }

    protected function runConcurrentMigrationsForMultipleEntities(
        MigrationSource $source,
        MigrationService $service,
    ): int {
        $config = $service->getConfig();
        $allSuccessful = true;

        $this->components->info('Running concurrent migrations for multiple entities...');

        foreach ($config->entities as $entity) {
            $this->components->info(sprintf('Starting concurrent migration for %s...', $entity));

            $result = $this->runConcurrentMigration(
                entity: $entity,
                source: $source,
                service: $service,
            );

            if ($result !== self::SUCCESS) {
                $allSuccessful = false;
                $this->components->error('Failed to migrate entity: '.$entity);
            }
        }

        if ($this->option('cleanup')) {
            $this->components->warn('Cleaning up...');
            $service->cleanup();
        }

        return $allSuccessful ? self::SUCCESS : self::FAILURE;
    }

    protected function promptForOptionalDependencies(MigrationService $service, MigrationSource $source, ?string $entity): void
    {
        $optionalDependencies = $service->getOptionalDependencies($source, $entity);

        if ($optionalDependencies === []) {
            return;
        }

        $this->components->info('Optional dependencies detected...');

        $options = [];

        foreach ($optionalDependencies as $dependency) {
            $label = $dependency->entityName;

            if ($dependency->description) {
                $label .= ' - '.$dependency->description;
            }

            $options[$dependency->entityName] = $label;
        }

        $selected = multiselect(
            label: 'Select optional dependencies to include:',
            options: $options,
        );

        $service->setOptionalDependencies($selected);
    }

    protected function displayResults(MigrationResult $result): void
    {
        if ($result->toTableRows() !== []) {
            $this->components->info('Migration results:');
            table(
                ['Entity', 'Migrated', 'Skipped', 'Failed'],
                $result->toTableRows(),
            );
        } else {
            $this->components->info('No results to display.');
        }
    }

    protected function displayVerboseResults(MigrationResult $result): void
    {
        if (! $this->output->isVerbose()) {
            return;
        }

        foreach ($result->entities as $entity => $stats) {
            if ($stats['skipped'] > 0) {
                $this->components->warn(sprintf('Skipped %s:', $entity));
                $skippedRecords = $result->getSkippedRecords($entity);

                if ($skippedRecords !== []) {
                    table(
                        array_keys($skippedRecords[0]),
                        array_map(array_values(...), $skippedRecords),
                    );
                }
            }

            if ($stats['failed'] > 0) {
                $this->components->error(sprintf('Failed %s:', $entity));
                $failedRecords = $result->getFailedRecords($entity);

                if ($failedRecords !== []) {
                    table(
                        array_keys($failedRecords[0]),
                        array_map(array_values(...), $failedRecords),
                    );
                }
            }

            if ($this->output->isVeryVerbose() && $stats['migrated'] > 0) {
                $this->components->info(sprintf('Migrated %s:', $entity));
                $migratedRecords = $result->getMigratedRecords($entity);

                if ($migratedRecords !== []) {
                    table(
                        array_keys($migratedRecords[0]),
                        array_map(array_values(...), $migratedRecords),
                    );
                }
            }
        }
    }

    protected function checkDatabaseConnection(MigrationSource $source): int
    {
        $this->components->info('Checking database connection...');

        try {
            $connection = $source->getConnection();
            DB::connection($connection)->getPdo();

            $databaseName = DB::connection($connection)->getDatabaseName();
            $driver = DB::connection($connection)->getDriverName();

            $this->components->success(sprintf('Successfully connected to database: %s (Driver: %s)', $databaseName, $driver));

            return self::SUCCESS;
        } catch (Exception $exception) {
            $this->components->error('Failed to connect to database. Error: '.$exception->getMessage());

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

            $result = Process::run('lsof -ti:'.$port);

            if ($result->failed() || trim($result->output()) === '') {
                return $port;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $sshConfig
     *
     * @throws RandomException
     */
    protected function createSshTunnel(array $sshConfig, string $connectionName): ?array
    {
        $dbConfig = config('database.connections.'.$connectionName);

        if (! $dbConfig) {
            return null;
        }

        $localPort = $this->findAvailablePort();

        if ($localPort === null) {
            $this->components->error('Could not find an available port for SSH tunnel.');

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
            $sshCommand .= ' -i '.$sshConfig['key'];
        } else {
            $sshCommand .= sprintf(" -i /dev/stdin <<< '%s'", $sshConfig['key']);
        }

        $result = Process::timeout(10)->run($sshCommand);

        if ($result->failed()) {
            $this->components->error('SSH tunnel creation failed: '.$result->errorOutput());

            if ($result->output()) {
                $this->components->error('Command output: '.$result->output());
            }

            return null;
        }

        Sleep::for(1)->second();

        config([sprintf('database.connections.%s.host', $connectionName) => '127.0.0.1']);
        config([sprintf('database.connections.%s.port', $connectionName) => $localPort]);

        DB::purge($connectionName);

        return [
            'local_port' => $localPort,
            'remote_host' => $remoteHost,
            'remote_port' => $remotePort,
            'ssh_config' => $sshConfig,
        ];
    }

    /**
     * @param  array<string, mixed>  $sshTunnel
     */
    protected function closeSshTunnel(array $sshTunnel): void
    {
        $localPort = $sshTunnel['local_port'];

        Process::pipe([
            'lsof -ti:'.$localPort,
            'xargs kill -9',
        ]);

        $this->components->info('SSH tunnel closed.');
    }

    protected function displayMigrationStatus(MigrationSource $source, ?string $entity = null): int
    {
        $this->components->info(sprintf('Fetching migration status from %s...', $source->getName()));

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
                    'record_count' => 'Error: '.$e->getMessage(),
                ];
            }
        }

        usort($statusData, fn (array $a, array $b): int => strcmp($a['entity'], $b['entity']));

        table(
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
                label: 'Which source would you like to start a migration from?',
                options: $service->getAvailableSources(),
            );
        }

        if (! in_array($source, $service->getAvailableSources())) {
            $this->components->error('Unknown migration source: '.$source);

            return null;
        }

        return $source;
    }

    protected function getSourceInstance(MigrationService $service, string $source): ?MigrationSource
    {
        $sourceInstance = $service->getSource($source);

        if (! $sourceInstance instanceof MigrationSource) {
            $this->components->error('Invalid migration source.');

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
            $this->components->error('SSH configuration not found for this source.');
            $this->components->warn('Please configure SSH credentials in your .env file:');
            $this->components->warn('MIGRATION_IC_SSH_HOST, MIGRATION_IC_SSH_USER, MIGRATION_IC_SSH_PORT, MIGRATION_IC_SSH_KEY');

            return null;
        }

        $this->components->info('Creating SSH tunnel...');
        $sshTunnel = $this->createSshTunnel($sshConfig, $sourceInstance->getConnection());

        if ($sshTunnel === null || $sshTunnel === []) {
            $this->components->error('Failed to create SSH tunnel.');

            return null;
        }

        $this->components->success('SSH tunnel established successfully.');

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
            $this->components->info('Base URL configured: '.$baseUrl);
        }
    }

    protected function calculateWorkerMemoryLimit(int $maxProcesses): ?int
    {
        $totalMemory = $this->getTotalSystemMemory();

        if ($totalMemory === null) {
            $this->components->error('Unable to determine system memory. Cannot calculate worker memory limit.');

            return null;
        }

        $osReservedMemory = (int) ($totalMemory * 0.25);
        $availableMemory = $totalMemory - $osReservedMemory;

        $workerMemoryLimit = (int) min((1024 * 1024 * 1024) / 2, floor($availableMemory / $maxProcesses));

        $memoryAfterAllocation = $totalMemory - ($workerMemoryLimit * $maxProcesses);
        $memoryAfterAllocationPercent = ($memoryAfterAllocation / $totalMemory) * 100;

        $totalMemoryString = Number::fileSize($totalMemory);
        $availableMemoryString = Number::fileSize($availableMemory);
        $workerMemoryLimitString = Number::fileSize($workerMemoryLimit);
        $memoryAfterAllocationString = Number::fileSize($memoryAfterAllocation);
        $memoryAfterAllocationPercentString = Number::percentage($memoryAfterAllocationPercent);

        $memoryString = [
            'Total System Memory: '.$totalMemoryString,
            'Available System Memory: '.$availableMemoryString,
            'Max Processes: '.$maxProcesses,
            'Memory Allocation Per Worker Process: '.$workerMemoryLimitString,
            sprintf('Reserved for OS: %s (%s)', $memoryAfterAllocationString, $memoryAfterAllocationPercentString),
        ];

        if (($workerMemoryLimit / 1024 / 1024) < 512) {
            $this->components->error('Insufficient memory available for worker processes');
            $this->components->error('At least 512MB should be allocatable for each worker process.');
            $this->components->bulletList($memoryString);

            $maxSafeProcesses = (int) floor($availableMemory / 1024 / 1024 / 1024);
            $this->components->warn(sprintf('Consider setting your --max-processes to %d or fewer.', $maxSafeProcesses));

            return null;
        }

        if ($memoryAfterAllocationPercent < 25) {
            $this->components->error('Insufficient memory available for the requested number of processes.');
            $this->components->error('At least 25% of total memory must remain available for the OS.');
            $this->components->bulletList($memoryString);

            $maxSafeProcesses = (int) floor($availableMemory / 1024 / 1024 / 1024);
            $this->components->warn(sprintf('Consider setting your --max-processes to %d or fewer.', $maxSafeProcesses));

            return null;
        }

        $this->components->info('Memory Information:');
        $this->components->bulletList($memoryString);

        return (int) floor($workerMemoryLimit / 1024 / 1024);
    }

    protected function getTotalSystemMemory(): ?int
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            $result = Process::run('sysctl -n hw.memsize');

            if ($result->successful()) {
                return (int) round((int) trim($result->output()));
            }
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $result = Process::run('grep MemTotal /proc/meminfo | awk \'{print $2}\'');

            if ($result->successful()) {
                return (int) round((int) trim($result->output()) * 1024);
            }
        }

        return null;
    }

    protected function buildMigrationConfig(MigrationSource $source): MigrationConfig
    {
        $excluded = [];
        $entity = $this->option('entity');

        if ($this->option('excluded')) {
            $excluded = array_map(trim(...), explode(',', $this->option('excluded')));
        }

        if ($entity) {
            $entities = array_map(trim(...), explode(',', $entity));
        } else {
            $entities = array_keys($source->getImporters());

            if ($excluded !== []) {
                $entities = array_filter($entities, fn (string $entityName): bool => ! in_array($entityName, $excluded));
            }
        }

        return new MigrationConfig(
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

    /**
     * @throws ReflectionException
     */
    protected function setupMigration(): void
    {
        app()->bind(InputInterface::class, $this->input);
        app()->bind(OutputInterface::class, $this->output);
    }
}
