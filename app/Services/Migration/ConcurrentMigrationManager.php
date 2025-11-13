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
            "Entity: $this->entity",
            "Total Records: $totalToProcess",
            "Worker Memory Limit: $workerMemoryLimit",
            "Max Records Per Process: {$this->config->maxRecordsPerProcess}",
            "Max Number of Processes: {$this->config->maxProcesses}",
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
        $this->components->success("All processes for $this->entity completed!");
        $this->components->info('Completed chunks: '.count($this->completedChunks));

        if ($this->failedChunks !== []) {
            $this->components->error('Failed chunks: '.count($this->failedChunks));

            foreach ($this->failedChunks as $chunk) {
                $this->components->error("Offset {$chunk['offset']}, Limit {$chunk['limit']}: {$chunk['error']}");
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
        ];

        $this->output->writeln("<comment>[Process Started]</comment> Entity: {$this->entity}, Offset: $offset, Limit: $limit, PID: {$process->getPid()}");
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

    protected function handleSuccessfulProcess(array $data): void
    {
        $this->completedChunks[] = $data;
        $this->output->writeln("<comment>[Process Completed]</comment> Entity: {$data['entity']}, Offset: {$data['offset']}, Limit: {$data['limit']}");
    }

    protected function handleFailedProcess(array $data, Process $process): void
    {
        $exitCode = $process->getExitCode();
        $errorOutput = in_array($process->getErrorOutput(), ['', '0'], true) ? 'Unknown error' : $process->getErrorOutput();
        $stdOutput = $process->getOutput();

        $this->failedChunks[] = [
            'offset' => $data['offset'],
            'limit' => $data['limit'],
            'entity' => $data['entity'],
            'error' => $errorOutput,
            'exit_code' => $exitCode,
        ];

        $this->output->writeln("<error>[Process Failed]</error> Entity: {$data['entity']}, Offset: {$data['offset']}, Exit Code: $exitCode");

        if ($errorOutput && ! $this->isProgressBarOutput($errorOutput)) {
            $this->components->error("Error: $errorOutput");
        }

        if ($stdOutput !== '' && $stdOutput !== '0') {
            $this->output->writeln("  → Output: $stdOutput");
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
