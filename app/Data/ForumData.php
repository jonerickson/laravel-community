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
class ForumData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public ?string $description = null;

    public ?int $categoryId = null;

    public ?int $parentId = null;

    public ?string $rules = null;

    public ?string $icon = null;

    public string $color;

    public int $order;

    public bool $isActive;

    public ?int $topicsCount = null;

    public ?int $postsCount = null;

    public ?bool $isFollowedByUser = null;

    public ?int $followersCount = null;

    /** @var TopicData[] */
    public ?array $latestTopics = null;

    #[LoadRelation]
    public ?ForumCategoryData $category = null;

    #[LoadRelation]
    public ?ForumData $parent = null;

    /** @var ForumData[] */
    public ?array $children = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
