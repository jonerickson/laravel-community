<?php

declare(strict_types=1);

namespace App\Services\Migration;

class MigrationConfig
{
    public function __construct(
        public array $entities = [],
        public int $batchSize = 1000,
        public ?int $limit = null,
        public ?int $offset = null,
        public ?int $userId = null,
        public bool $isDryRun = false,
        public bool $useSsh = false,
        public bool $downloadMedia = true,
        public ?string $baseUrl = null,
        public bool $parallel = false,
        public int $maxRecordsPerProcess = 1000,
        public int $maxProcesses = 4,
        public ?int $memoryLimit = null,
        public array $excluded = [],
    ) {
        //
    }
}
