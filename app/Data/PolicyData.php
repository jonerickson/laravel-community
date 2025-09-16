<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class PolicyData extends Data
{
    public int $id;

    public string $title;

    public string $slug;

    public ?string $version;

    public ?string $description;

    public string $content;

    public bool $isActive;

    public UserData $author;

    public PolicyCategoryData $category;

    public ?CarbonImmutable $effectiveAt;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;
}
