<?php

declare(strict_types=1);

namespace App\Services\Migration\Contracts;

use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationResult;
use Illuminate\Console\OutputStyle;

interface EntityImporter
{
    public function getEntityName(): string;

    /**
     * @return array<ImporterDependency>
     */
    public function getDependencies(): array;

    public function import(
        string $connection,
        int $batchSize,
        bool $isDryRun,
        OutputStyle $output,
        MigrationResult $result,
    ): void;
}
