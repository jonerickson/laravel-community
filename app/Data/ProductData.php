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

    public ?ProductTaxCode $taxCode = null;

    public bool $isFeatured;

    public bool $isSubscriptionOnly;

    public bool $isMarketplaceProduct;

    public int $trialDays;

    public bool $allowPromotionCodes;

    public bool $allowDiscountCodes;

    public ?string $featuredImage = null;

    public ?string $featuredImageUrl = null;

    public ?string $externalProductId = null;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $metadata = null;

    /** @var PriceData[] */
    public array $prices;

    #[LoadRelation]
    public ?PriceData $defaultPrice = null;

    public float $averageRating;

    public int $reviewsCount;

    /** @var ProductCategoryData[] */
    public array $categories;

    /** @var PolicyData[] */
    public array $policies;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
