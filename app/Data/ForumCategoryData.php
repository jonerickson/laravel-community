<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ForumCategoryData extends Data
{
    public int $id;

    public ?int $parentId = null;

    #[LoadRelation]
    public ?ForumCategoryData $parent = null;

    public string $name;

    public string $slug;

    public ?string $description = null;

    public ?string $icon = null;

    public string $color;

    public int $order;

    public bool $isActive;

    /** @var ForumCategoryData[] */
    public ?array $children = null;

    /** @var ForumData[] */
    public ?array $forums = null;

    #[LoadRelation]
    public ?ImageData $image = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
