<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ProductTaxCode;
use App\Enums\ProductType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\LoadRelation;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ProductData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public string $description;

    public ProductType $type;

    public ?ProductTaxCode $taxCode;

    public bool $isFeatured;

    public bool $isSubscriptionOnly;

    public int $trialDays;

    public bool $allowPromotionCodes;

    public ?string $featuredImage;

    public ?string $featuredImageUrl;

    public ?string $externalProductId;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $metadata;

    /** @var PriceData[] */
    public array $prices;

    #[LoadRelation]
    public ?PriceData $defaultPrice;

    public ?float $averageRating;

    public int $reviewsCount;

    /** @var ProductCategoryData[] */
    public array $categories;

    /** @var PolicyData[] */
    public array $policies;

    public ?CarbonImmutable $createdAt;

    public ?CarbonImmutable $updatedAt;
}
