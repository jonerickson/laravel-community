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
class SearchResultData extends Data
{
    public function __construct(
        public int $id,
        public string $type,
        public string $title,
        public string $url,
        public ?string $description = null,
        public ?string $excerpt = null,
        public ?string $version = null,
        public ?string $price = null,
        public ?string $forumName = null,
        public ?string $categoryName = null,
        public ?string $authorName = null,
        public ?string $postType = null,
        public ?CarbonImmutable $effectiveAt = null,
        public ?CarbonImmutable $createdAt = null,
        public ?CarbonImmutable $updatedAt = null,
    ) {}
}
