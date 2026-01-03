<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Traits\AddsForumPermissions;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ForumCategoryData extends Data
{
    use AddsForumPermissions;

    public int $id;

    public string $name;

    public string $slug;

    public ?string $description = null;

    public ?string $icon = null;

    public string $color;

    public int $order;

    public int $postsCount;

    public bool $isActive;

    public ?string $featuredImage = null;

    public ?string $featuredImageUrl = null;

    /** @var ForumData[] */
    public ?array $forums = null;

    /** @var GroupData[] */
    public ?array $groups = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
