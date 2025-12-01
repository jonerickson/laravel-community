<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryTransactionType;
use App\Traits\HasAuthor;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $inventory_item_id
 * @property InventoryTransactionType $type
 * @property int $quantity
 * @property int $quantity_before
 * @property int $quantity_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $reason
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read User|null $creator
 * @property-read InventoryItem $inventoryItem
 * @property-read Model|Eloquent|null $reference
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereInventoryItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereQuantityAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereQuantityBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InventoryTransaction whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class InventoryTransaction extends Model
{
    use HasAuthor;

    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'reason',
        'notes',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'type' => InventoryTransactionType::class,
            'quantity' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
        ];
    }
}
