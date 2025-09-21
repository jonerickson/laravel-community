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
class CartItemData extends Data
{
    public int $productId;

    public ?int $priceId = null;

    public string $name;

    public string $slug;

    public int $quantity;

    public ?ProductData $product = null;

    public ?PriceData $selectedPrice = null;

    /** @var PriceData[] */
    public array $availablePrices;

    public ?CarbonImmutable $addedAt = null;
}
