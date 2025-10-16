<?php

declare(strict_types=1);

namespace App\Services\Migration;

enum DependencyType: string
{
    case Pre = 'pre';
    case Post = 'post';
}

class ImporterDependency
{
    public function __construct(
        public readonly string $entityName,
        public readonly DependencyType $type,
        public readonly bool $required,
        public readonly ?string $description = null,
    ) {}

    public static function requiredPre(string $entityName, ?string $description = null): self
    {
        return new self($entityName, DependencyType::Pre, true, $description);
    }

    public static function optionalPre(string $entityName, ?string $description = null): self
    {
        return new self($entityName, DependencyType::Pre, false, $description);
    }

    public static function requiredPost(string $entityName, ?string $description = null): self
    {
        return new self($entityName, DependencyType::Post, true, $description);
    }

    public static function optionalPost(string $entityName, ?string $description = null): self
    {
        return new self($entityName, DependencyType::Post, false, $description);
    }

    public function isPre(): bool
    {
        return $this->type === DependencyType::Pre;
    }

    public function isPost(): bool
    {
        return $this->type === DependencyType::Post;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isOptional(): bool
    {
        return ! $this->required;
    }
}
