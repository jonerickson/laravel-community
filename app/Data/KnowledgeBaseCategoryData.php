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
class KnowledgeBaseCategoryData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public ?string $description = null;

    public ?string $icon = null;

    public ?string $color = null;

    public int $order;

    public int $articlesCount;

    public bool $isActive;

    public ?string $featuredImage = null;

    public ?string $featuredImageUrl = null;

    /** @var KnowledgeBaseArticleData[] */
    public ?array $articles = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
