<?php

declare(strict_types=1);

namespace App\Services\Migration;

use App\Services\Migration\Contracts\MigrationSource;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Sleep;
use Symfony\Component\Process\Process;

class ConcurrentMigrationManager
{
    protected array $activeProcesses = [];

    protected array $completedChunks = [];

    protected array $failedChunks = [];

    protected array $workerColors = [];

    protected array $availableColors = [
        'cyan',
        'magenta',
        'yellow',
        'blue',
        'green',
        'red',
    ];

    public function __construct(
        protected MigrationConfig $config,
        protected OutputStyle $output,
        protected ?int $workerMemoryLimit = null,
    ) {}

    public function migrate(
        MigrationSource $source,
        int $totalRecords,
    ): bool {
        $startOffset = $this->config->offset ?? 0;
        $nextOffset = $startOffset;
        $totalToProcess = $totalRecords - $startOffset;

        $this->output->writeln("Starting concurrent migration of <info>{$this->config->entity}</info>");
        $this->output->writeln("Total records: <info>$totalToProcess</info>");
        $this->output->writeln("Max records per process: <info>{$this->config->maxRecordsPerProcess}</info>");
        $this->output->writeln("Concurrent processes: <info>{$this->config->maxProcesses}</info>");
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
        $this->output->writeln('<info>All processes completed!</info>');
        $this->output->writeln('Completed chunks: <info>'.count($this->completedChunks).'</info>');

        if ($this->failedChunks !== []) {
            $this->output->writeln('<error>Failed chunks: '.count($this->failedChunks).'</error>');

            foreach ($this->failedChunks as $chunk) {
                $this->output->writeln("<error>  - Offset {$chunk['offset']}, Limit {$chunk['limit']}: {$chunk['error']}</error>");
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
    ): void {
        $command = [
            PHP_BINARY,
            'artisan',
            'mi:migrate',
            $source->getName(),
            '--entity='.$this->config->entity,
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

        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->start();

        $color = $this->assignWorkerColor($offset);

        $this->activeProcesses[$offset] = [
            'process' => $process,
            'offset' => $offset,
            'limit' => $limit,
            'entity' => $this->config->entity,
            'output_position' => 0,
            'error_position' => 0,
            'color' => $color,
        ];

        $this->output->writeln("<comment>[Process Started]</comment> Entity: {$this->config->entity}, Offset: $offset, Limit: $limit, PID: {$process->getPid()}");
    }

    protected function checkProcesses(): void
    {
        foreach ($this->activeProcesses as $offset => &$data) {
            /** @var Process $process */
            $process = $data['process'];

            $this->streamProcessOutput($process, $data);

            if (! $process->isRunning()) {
                $this->streamProcessOutput($process, $data);

                if ($process->isSuccessful()) {
                    $this->completedChunks[] = $data;
                    $this->output->writeln("<info>[Process Completed]</info> Entity: {$data['entity']}, Offset: {$data['offset']}, Limit: {$data['limit']}");
                } else {
                    $exitCode = $process->getExitCode();
                    $stdOutput = $process->getOutput();
                    $errorOutput = $process->getErrorOutput() ?: 'Unknown error';

                    $errorMessage = $errorOutput;

                    if ($exitCode === 137) {
                        $errorMessage = 'Process killed (likely out of memory - exit code 137). Try reducing --max-records-per-process or --max-processes.';
                    }

                    $this->failedChunks[] = [
                        'offset' => $data['offset'],
                        'limit' => $data['limit'],
                        'entity' => $data['entity'],
                        'error' => $errorMessage,
                        'exit_code' => $exitCode,
                    ];

                    $this->output->writeln("<error>[Process Failed]</error> Entity: {$data['entity']}, Offset: {$data['offset']}, Exit Code: $exitCode");

                    if ($errorOutput && ! $this->isProgressBarOutput($errorOutput)) {
                        $this->output->writeln("<error>  → Error: $errorOutput</error>");
                    }

                    if ($stdOutput) {
                        $this->output->writeln("<info>  → Output: $stdOutput</info>");
                    }
                }

                $this->releaseWorkerColor($offset);
                unset($this->activeProcesses[$offset]);
            }
        }
    }

    protected function streamProcessOutput(Process $process, array &$data): void
    {
        $newOutput = substr($process->getOutput(), $data['output_position']);
        $newError = substr($process->getErrorOutput(), $data['error_position']);
        $color = $data['color'] ?? 'comment';

        if ($newOutput !== '' && $newOutput !== '0') {
            $lines = explode("\n", rtrim($newOutput, "\n"));
            foreach ($lines as $line) {
                if ($line !== '' && $line !== '0' && ! $this->isProgressBarOutput($line)) {
                    $this->output->writeln("<fg=$color>[Worker {$data['offset']}]</> $line");
                }
            }
            $data['output_position'] += strlen($newOutput);
        }

        if ($newError !== '' && $newError !== '0') {
            $lines = explode("\n", rtrim($newError, "\n"));
            foreach ($lines as $line) {
                if ($line !== '' && $line !== '0' && ! $this->isProgressBarOutput($line)) {
                    $this->output->writeln("<fg=$color>[Worker {$data['offset']} ERROR]</> $line");
                }
            }
            $data['error_position'] += strlen($newError);
        }
    }

    protected function isProgressBarOutput(string $line): bool
    {
        return preg_match('/[\x1B\x9B][\[\]()#;?]*(?:\d{1,4}(?:;\d{0,4})*)?[0-9A-ORZcf-nqry=><]/', $line)
            || str_contains($line, "\r")
            || preg_match('/^\s*[\-=>\s]+\s*\d+/', $line);
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
