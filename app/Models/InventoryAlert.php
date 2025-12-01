<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryAlertType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_item_id
 * @property InventoryAlertType $alert_type
 * @property int|null $threshold_value
 * @property int|null $current_value
 * @property bool $is_resolved
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read InventoryItem $inventoryItem
 * @property-read User|null $resolvedBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereAlertType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereCurrentValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereInventoryItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereIsResolved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereResolvedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereThresholdValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryAlert whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class InventoryAlert extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'alert_type',
        'threshold_value',
        'current_value',
        'is_resolved',
        'resolved_at',
        'resolved_by',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    protected function casts(): array
    {
        return [
            'alert_type' => InventoryAlertType::class,
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'threshold_value' => 'integer',
            'current_value' => 'integer',
        ];
    }
}
