<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Eloquent;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin Eloquent
 */
trait HasInventory
{
    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'product_id');
    }

    public function inventoryTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            InventoryTransaction::class,
            InventoryItem::class,
            'product_id',
            'inventory_item_id'
        );
    }

    public function hasStock(int $quantity = 1): bool
    {
        $inventory = $this->inventoryItem;

        if (! $inventory || ! $inventory->track_inventory) {
            return true;
        }

        return $inventory->canFulfillQuantity($quantity);
    }
}
