<?php

declare(strict_types=1);

namespace App\Services\Migration\Contracts;

interface MigrationSource
{
    public function getName(): string;

    public function getConnection(): string;

    public function getImporters(): array;

    public function getImporter(string $entity): ?EntityImporter;
}
