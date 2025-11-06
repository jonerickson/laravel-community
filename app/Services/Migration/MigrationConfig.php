<?php

declare(strict_types=1);

namespace App\Services\Migration;

class MigrationConfig
{
    public function __construct(
        public ?string $entity = null,
        public int $batchSize = 1000,
        public ?int $limit = null,
        public ?int $offset = null,
        public ?int $userId = null,
        public bool $isDryRun = false,
        public bool $useSsh = false,
        public ?string $baseUrl = null,
        public bool $parallel = false,
        public int $maxRecordsPerProcess = 1000,
        public int $maxProcesses = 4,
        public ?int $memoryLimit = null,
    ) {}

    public function withEntity(?string $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    public function withBatchSize(int $batchSize): self
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    public function withLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function withOffset(?int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function withUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function withDryRun(bool $isDryRun): self
    {
        $this->isDryRun = $isDryRun;

        return $this;
    }

    public function withSsh(bool $useSsh): self
    {
        $this->useSsh = $useSsh;

        return $this;
    }

    public function withBaseUrl(?string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function withParallel(bool $parallel): self
    {
        $this->parallel = $parallel;

        return $this;
    }

    public function withMaxRecordsPerProcess(int $maxRecordsPerProcess): self
    {
        $this->maxRecordsPerProcess = $maxRecordsPerProcess;

        return $this;
    }

    public function withMaxProcesses(int $maxProcesses): self
    {
        $this->maxProcesses = $maxProcesses;

        return $this;
    }

    public function withMemoryLimit(?int $memoryLimit): self
    {
        $this->memoryLimit = $memoryLimit;

        return $this;
    }
}
