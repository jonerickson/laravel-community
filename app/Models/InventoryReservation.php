<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_item_id
 * @property int|null $order_id
 * @property int $quantity
 * @property InventoryReservationStatus $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $fulfilled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read InventoryItem $inventoryItem
 * @property-read Order|null $order
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereFulfilledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereInventoryItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryReservation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class InventoryReservation extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'order_id',
        'quantity',
        'status',
        'expires_at',
        'fulfilled_at',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    protected function casts(): array
    {
        return [
            'status' => InventoryReservationStatus::class,
            'quantity' => 'integer',
            'expires_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }
}
