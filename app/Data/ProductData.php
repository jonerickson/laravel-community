<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductTaxCode;
use App\Enums\ProductType;
use Carbon\CarbonImmutable;
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

    public string $referenceId;

    public string $name;

    public string $slug;

    public ?string $description = null;

    public ProductType $type;

    public int $order;

    public ?ProductTaxCode $taxCode = null;

    public bool $isFeatured;

    public bool $isSubscriptionOnly;

    public bool $isMarketplaceProduct;

    public ?UserData $seller = null;

    public ProductApprovalStatus $approvalStatus;

    public bool $isActive;

    public bool $isVisible;

    public int $trialDays;

    public bool $allowPromotionCodes;

    public bool $allowDiscountCodes;

    public ?string $featuredImage = null;

    public ?string $featuredImageUrl = null;

    /** @var ImageData[] */
    public array $images = [];

    public ?string $externalProductId = null;

    #[LiteralTypeScriptType('Array<string, unknown> | null')]
    public ?array $metadata = null;

    /** @var PriceData[] */
    public array $prices;

    public ?PriceData $defaultPrice = null;

    public float $averageRating;

    public int $reviewsCount;

    /** @var ProductCategoryData[] */
    public array $categories;

    /** @var PolicyData[] */
    public array $policies;

    public ?InventoryItemData $inventoryItem = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
