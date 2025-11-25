<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\KnowledgeBaseArticleType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class KnowledgeBaseArticleData extends Data
{
    public int $id;

    public KnowledgeBaseArticleType $type;

    public string $title;

    public string $slug;

    public ?string $excerpt = null;

    public string $content;

    public bool $isPublished;

    public ?int $categoryId = null;

    public ?KnowledgeBaseCategoryData $category = null;

    public ?string $featuredImage = null;

    public ?string $featuredImageUrl = null;

    public ?int $readingTime = null;

    public ?CarbonImmutable $publishedAt = null;

    public ?int $createdBy = null;

    public UserData $author;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $metadata = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
