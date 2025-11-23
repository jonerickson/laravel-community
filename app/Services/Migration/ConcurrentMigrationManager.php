<?php

declare(strict_types=1);

namespace App\Services\Migration;

use App\Services\Migration\Contracts\MigrationSource;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Number;
use Illuminate\Support\Sleep;
use Symfony\Component\Process\Process;

class ConcurrentMigrationManager
{
    protected array $activeProcesses = [];

    protected array $completedChunks = [];

    protected array $failedChunks = [];

    protected array $workerColors = [];

    protected array $retryCount = [];

    protected int $maxRetries = 3;

    /**
     * @var string[]
     */
    protected array $availableColors = [
        'cyan',
        'magenta',
        'yellow',
        'blue',
        'green',
        'red',
    ];

    public function __construct(
        protected string $entity,
        protected MigrationConfig $config,
        protected OutputStyle $output,
        protected Factory $components,
        protected ?int $workerMemoryLimit = null,
    ) {}

    public function migrate(
        MigrationSource $source,
        int $totalRecords,
    ): bool {
        $startOffset = $this->config->offset ?? 0;
        $nextOffset = $startOffset;
        $totalToProcess = $totalRecords - $startOffset;
        $workerMemoryLimit = Number::fileSize(($this->workerMemoryLimit ?? 0) * 1024 * 1024);

        $this->components->info('Concurrency Information:');
        $this->components->bulletList([
            'Entity: '.$this->entity,
            'Total Records: '.$totalToProcess,
            'Worker Memory Limit: '.$workerMemoryLimit,
            'Max Records Per Process: '.$this->config->maxRecordsPerProcess,
            'Max Number of Processes: '.$this->config->maxProcesses,
        ]);
        $this->output->newLine();

        while ($nextOffset < $totalRecords || $this->activeProcesses !== []) {
            while (count($this->activeProcesses) < $this->config->maxProcesses && $nextOffset < $totalRecords) {
                $chunkLimit = min($this->config->maxRecordsPerProcess, $totalRecords - $nextOffset);

                $this->spawnWorkerProcess(
                    source: $source,
                    offset: $nextOffset,
                    limit: $chunkLimit,
                );

                $nextOffset += $chunkLimit;
            }

            $this->checkProcesses();

            Sleep::for(100000)->microseconds();
        }

        $this->output->newLine();
        $this->components->success(sprintf('All processes for %s completed!', $this->entity));
        $this->components->info('Completed chunks: '.count($this->completedChunks));

        $totalRetries = array_sum($this->retryCount);
        if ($totalRetries > 0) {
            $this->components->info(sprintf('Total retries performed: %d', $totalRetries));
        }

        if ($this->failedChunks !== []) {
            $this->components->error('Failed chunks: '.count($this->failedChunks));

            foreach ($this->failedChunks as $chunk) {
                $retryInfo = isset($chunk['retry_attempts']) ? sprintf(' (Failed after %d retries)', $chunk['retry_attempts']) : '';
                $this->components->error(sprintf('Offset %s, Limit %s%s: %s', $chunk['offset'], $chunk['limit'], $retryInfo, $chunk['error']));
            }
        }

        return $this->failedChunks === [];
    }

    public function terminateAll(): void
    {
        foreach ($this->activeProcesses as $data) {
            /** @var Process $process */
            $process = $data['process'];

            if ($process->isRunning()) {
                $process->stop(3, SIGTERM);
            }
        }

        $this->activeProcesses = [];
    }

    protected function spawnWorkerProcess(
        MigrationSource $source,
        int $offset,
        int $limit,
        int $retryAttempt = 0,
    ): void {
        $command = [
            PHP_BINARY,
            'artisan',
            'app:migrate',
            $source->getName(),
            '--entity='.$this->entity,
            '--offset='.$offset,
            '--limit='.$limit,
            '--batch='.$this->config->batchSize,
            '--worker',
            '--force',
        ];

        if ($this->workerMemoryLimit !== null) {
            $command[] = '--memory-limit='.$this->workerMemoryLimit;
        }

        if ($this->config->isDryRun) {
            $command[] = '--dry-run';
        }

        if ($this->config->useSsh) {
            $command[] = '--ssh';
        }

        if ($this->config->userId !== null && $this->config->userId !== 0) {
            $command[] = '--id='.$this->config->userId;
        }

        if ($this->config->excluded !== []) {
            $command[] = '--excluded='.implode(',', $this->config->excluded);
        }

        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->start();

        $color = $this->assignWorkerColor($offset);

        $this->activeProcesses[$offset] = [
            'process' => $process,
            'offset' => $offset,
            'limit' => $limit,
            'entity' => $this->entity,
            'output_position' => 0,
            'error_position' => 0,
            'color' => $color,
            'retry_attempt' => $retryAttempt,
            'source' => $source,
        ];

        $retryMessage = $retryAttempt > 0 ? sprintf(' (Retry %d/%d)', $retryAttempt, $this->maxRetries) : '';
        $this->output->writeln(sprintf('<comment>[Process Started]</comment> Entity: %s, Offset: %d, Limit: %d, PID: %s%s', $this->entity, $offset, $limit, $process->getPid(), $retryMessage));
    }

    protected function checkProcesses(): void
    {
        foreach ($this->activeProcesses as $offset => &$data) {
            /** @var Process $process */
            $process = $data['process'];

            $this->streamProcessOutput($process, $data);

            if ($process->isRunning()) {
                continue;
            }

            if ($process->isSuccessful()) {
                $this->handleSuccessfulProcess($data);
            } else {
                $this->handleFailedProcess($data, $process);
            }

            $this->releaseWorkerColor($offset);
            unset($this->activeProcesses[$offset]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleSuccessfulProcess(array $data): void
    {
        $this->completedChunks[] = $data;
        $this->output->writeln(sprintf('<comment>[Process Completed]</comment> Entity: %s, Offset: %s, Limit: %s', $data['entity'], $data['offset'], $data['limit']));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleFailedProcess(array $data, Process $process): void
    {
        $exitCode = $process->getExitCode();
        $errorOutput = in_array($process->getErrorOutput(), ['', '0'], true) ? 'Unknown error' : $process->getErrorOutput();
        $stdOutput = $process->getOutput();
        $retryAttempt = $data['retry_attempt'] ?? 0;

        $this->output->writeln(sprintf('<error>[Process Failed]</error> Entity: %s, Offset: %s, Exit Code: %s', $data['entity'], $data['offset'], $exitCode));

        if ($stdOutput !== '' && $stdOutput !== '0') {
            $this->components->info('Output:');
            $lines = array_filter(explode("\n", $stdOutput), fn (string $line): bool => trim($line) !== '');
            $this->output->writeln(implode("\n", $lines));
            $this->output->newLine();
        }

        if ($retryAttempt < $this->maxRetries) {
            $nextRetry = $retryAttempt + 1;
            $chunkKey = sprintf('%d-%d', $data['offset'], $data['limit']);

            if (! isset($this->retryCount[$chunkKey])) {
                $this->retryCount[$chunkKey] = 0;
            }

            $this->retryCount[$chunkKey]++;

            $this->output->writeln(sprintf('<info>[Scheduling Retry]</info> Entity: %s, Offset: %s, Attempt: %d/%d', $data['entity'], $data['offset'], $nextRetry, $this->maxRetries));

            $this->spawnWorkerProcess(
                source: $data['source'],
                offset: $data['offset'],
                limit: $data['limit'],
                retryAttempt: $nextRetry,
            );
        } else {
            $this->failedChunks[] = [
                'offset' => $data['offset'],
                'limit' => $data['limit'],
                'entity' => $data['entity'],
                'error' => $errorOutput,
                'exit_code' => $exitCode,
                'retry_attempts' => $retryAttempt,
            ];

            $this->output->writeln(sprintf('<error>[Max Retries Reached]</error> Entity: %s, Offset: %s, Retry Attempts: %d', $data['entity'], $data['offset'], $retryAttempt));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function streamProcessOutput(Process $process, array &$data): void
    {
        $this->handleStreamChunk(
            $process->getOutput(),
            $data['output_position'],
            $data['offset'],
            $data['color'] ?? 'comment',
            false,
        );

        $this->handleStreamChunk(
            $process->getErrorOutput(),
            $data['error_position'],
            $data['offset'],
            $data['color'] ?? 'comment',
            true,
        );
    }

    protected function handleStreamChunk(
        string $buffer,
        int &$position,
        int $workerOffset,
        string $color,
        bool $isError
    ): void {
        $chunk = substr($buffer, $position);

        if ($chunk === '' || $chunk === '0') {
            return;
        }

        $lines = explode("\n", rtrim($chunk, "\n"));

        $isInsideTable = false;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if ($line === '0') {
                continue;
            }

            if ($this->isProgressBarOutput($line)) {
                continue;
            }

            $line = trim($line);

            if ($this->isUnicodeTableBorder($line)) {
                $isInsideTable = true;
                $this->output->writeln($line);

                continue;
            }

            if ($isInsideTable) {
                if ($this->isUnicodeTableRow($line)) {
                    $this->output->writeln($line);

                    continue;
                }

                $isInsideTable = false;
            }

            $prefix = sprintf(
                '<fg=%s>[Worker %s%s]</> ',
                $color,
                $workerOffset,
                $isError ? ' ERROR' : '',
            );

            $this->output->writeln($prefix.$line);
        }

        $position += strlen($chunk);
    }

    protected function isUnicodeTableBorder(string $line): bool
    {
        return preg_match('/^[┌├└][─┬┼┴]+[┐┤┘]$/u', $this->stripAnsi($line)) === 1;
    }

    protected function isUnicodeTableRow(string $line): bool
    {
        return preg_match('/^│.*│$/u', $this->stripAnsi($line)) === 1;
    }

    protected function isProgressBarOutput(string $line): bool
    {
        return (bool) preg_match('/^\s*\d+\/\d+\s*\[.*]\s*\d+%/', $this->stripAnsi($line));
    }

    protected function stripAnsi(string $line): string
    {
        return preg_replace('/\e\[[0-9;]*m/', '', $line);
    }

    protected function assignWorkerColor(int $offset): string
    {
        $usedColors = array_values($this->workerColors);

        foreach ($this->availableColors as $color) {
            if (! in_array($color, $usedColors)) {
                $this->workerColors[$offset] = $color;

                return $color;
            }
        }

        $colorIndex = count($this->workerColors) % count($this->availableColors);
        $color = $this->availableColors[$colorIndex];

        $this->workerColors[$offset] = $color;

        return $color;
    }

    protected function releaseWorkerColor(int $offset): void
    {
        unset($this->workerColors[$offset]);
    }
}
