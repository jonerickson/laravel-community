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
class OrderItemData extends Data
{
    public int $id;

    public int $orderId;

    public ?string $name = null;

    public ?int $productId = null;

    public ?int $priceId = null;

    public int $quantity;

    public ?float $amount = null;

    public bool $isOneTime;

    public bool $isRecurring;

    public ?ProductData $product = null;

    public ?PriceData $price = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;
}
