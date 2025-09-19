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
class ForumData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public ?string $description;

    public ?int $categoryId;

    public ?string $rules;

    public ?string $icon;

    public string $color;

    public int $order;

    public bool $isActive;

    public ?int $topicsCount;

    public ?int $postsCount;

    /** @var TopicData[] */
    public ?array $latestTopics;

    public ForumCategoryData $category;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;
}
