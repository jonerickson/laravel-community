<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class InventoryItemData extends Data
{
    public int $id;

    public int $productId;

    public string $sku;

    public int $quantityAvailable;

    public int $quantityReserved;

    public int $quantityDamaged;

    public int $quantityOnHand;

    public ?int $reorderPoint = null;

    public ?int $reorderQuantity = null;

    public ?string $warehouseLocation = null;

    public bool $trackInventory;

    public bool $allowBackorder;

    public bool $isLowStock;

    public bool $isOutOfStock;
}
