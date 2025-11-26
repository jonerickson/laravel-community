<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ProductCategoryData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public ?string $description = null;

    public ?int $parentId = null;

    public ?string $featuredImage = null;

    public ?string $featuredImageUrl = null;

    public bool $isVisible = true;

    public bool $isActive = true;

    public ?ProductCategoryData $parent = null;

    /** @var ProductCategoryData[] */
    public ?array $children = null;
}
