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

    public string $name;

    public string $slug;

    public ?string $description;

    public ?string $icon;

    public string $color;

    public int $order;

    public bool $isActive;

    /** @var ForumData[] */
    public ?array $forums;

    #[LoadRelation]
    public ?ImageData $image;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;
}
