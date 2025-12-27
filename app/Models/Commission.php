<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommissionStatus;
use App\Events\CommissionCreated;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $seller_id
 * @property int|null $order_id
 * @property int|null $payout_id
 * @property float $amount
 * @property CommissionStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Order|null $order
 * @property-read Payout|null $payout
 * @property-read User|null $seller
 *
 * @method static \Database\Factories\CommissionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission wherePayoutId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereSellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Commission extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => CommissionStatus::Pending,
    ];

    protected $fillable = [
        'seller_id',
        'payout_id',
        'order_id',
        'payout',
        'amount',
        'status',
    ];

    protected $dispatchesEvents = [
        'created' => CommissionCreated::class,
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): float => (float) $value / 100,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'status' => CommissionStatus::class,
        ];
    }
}
