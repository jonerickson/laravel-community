<?php

declare(strict_types=1);

namespace App\Services\Migration\Contracts;

interface MigrationSource
{
    public function getName(): string;

    public function getConnection(): string;

    /**
     * @return array<string, EntityImporter>
     */
    public function getImporters(): array;

    public function getImporter(string $entity): ?EntityImporter;

    /**
     * @return array<string, string|int>|null
     */
    public function getSshConfig(): ?array;

    public function getBaseUrl(): ?string;

    public function setBaseUrl(?string $url): void;

    public function cleanup(): void;
}
