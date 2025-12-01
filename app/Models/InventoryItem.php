<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string|null $sku
 * @property int $quantity_available
 * @property int $quantity_reserved
 * @property int $quantity_damaged
 * @property int|null $reorder_point
 * @property int|null $reorder_quantity
 * @property string|null $warehouse_location
 * @property bool $track_inventory
 * @property bool $allow_backorder
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InventoryAlert> $alerts
 * @property-read int|null $alerts_count
 * @property-read bool $is_low_stock
 * @property-read bool $is_out_of_stock
 * @property-read Product $product
 * @property-read int $quantity_on_hand
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InventoryReservation> $reservations
 * @property-read int|null $reservations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, InventoryTransaction> $transactions
 * @property-read int|null $transactions_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereAllowBackorder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereQuantityAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereQuantityDamaged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereQuantityReserved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereReorderPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereReorderQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereTrackInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryItem whereWarehouseLocation($value)
 *
 * @mixin \Eloquent
 */
class InventoryItem extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'quantity_available',
        'quantity_reserved',
        'quantity_damaged',
        'reorder_point',
        'reorder_quantity',
        'warehouse_location',
        'track_inventory',
        'allow_backorder',
    ];

    protected $appends = [
        'is_low_stock',
        'is_out_of_stock',
        'quantity_on_hand',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(InventoryAlert::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function quantityOnHand(): Attribute
    {
        return Attribute::get(fn (): int => $this->quantity_available + $this->quantity_reserved);
    }

    public function isLowStock(): Attribute
    {
        return Attribute::get(fn (): bool => $this->reorder_point && $this->quantity_available <= $this->reorder_point);
    }

    public function isOutOfStock(): Attribute
    {
        return Attribute::get(fn (): bool => $this->quantity_available <= 0 && ! $this->allow_backorder);
    }

    public function canFulfillQuantity(int $quantity): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->quantity_available >= $quantity || $this->allow_backorder;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'track_inventory' => 'boolean',
            'allow_backorder' => 'boolean',
            'quantity_available' => 'integer',
            'quantity_reserved' => 'integer',
            'quantity_damaged' => 'integer',
            'reorder_point' => 'integer',
            'reorder_quantity' => 'integer',
        ];
    }
}
