<?php

declare(strict_types=1);

namespace App\Services\Migration\Contracts;

use App\Services\Migration\ImporterDependency;
use App\Services\Migration\MigrationConfig;
use App\Services\Migration\MigrationResult;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;

interface EntityImporter
{
    public function getEntityName(): string;

    public function getSourceTable(): string;

    public function getTotalRecordsCount(): int;

    /**
     * @return array<ImporterDependency>
     */
    public function getDependencies(): array;

    public function import(
        MigrationResult $result,
        OutputStyle $output,
        Factory $components,
    ): int;

    public function isCompleted(): bool;

    public function markCompleted(): void;

    public function cleanup(): void;

    public function setConfig(MigrationConfig $config): self;

    public function getConfig(): ?MigrationConfig;
}
