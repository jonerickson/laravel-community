<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $order_number
 * @property OrderStatus $status
 * @property int|null $amount
 * @property string|null $invoice_url
 * @property string|null $external_invoice_id
 * @property string|null $external_checkout_id
 * @property string|null $external_order_id
 * @property string|null $external_payment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
 * @property-read int|null $items_count
 * @property-read User $user
 *
 * @method static Builder<static>|Order completed()
 * @method static \Database\Factories\OrderFactory factory($count = null, $state = [])
 * @method static Builder<static>|Order newModelQuery()
 * @method static Builder<static>|Order newQuery()
 * @method static Builder<static>|Order query()
 * @method static Builder<static>|Order readyToView()
 * @method static Builder<static>|Order whereAmount($value)
 * @method static Builder<static>|Order whereCreatedAt($value)
 * @method static Builder<static>|Order whereExternalCheckoutId($value)
 * @method static Builder<static>|Order whereExternalInvoiceId($value)
 * @method static Builder<static>|Order whereExternalOrderId($value)
 * @method static Builder<static>|Order whereExternalPaymentId($value)
 * @method static Builder<static>|Order whereId($value)
 * @method static Builder<static>|Order whereInvoiceUrl($value)
 * @method static Builder<static>|Order whereOrderNumber($value)
 * @method static Builder<static>|Order whereStatus($value)
 * @method static Builder<static>|Order whereUpdatedAt($value)
 * @method static Builder<static>|Order whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Order extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => OrderStatus::RequiresCapture,
    ];

    protected $fillable = [
        'user_id',
        'status',
        'amount',
        'order_number',
        'external_order_id',
        'external_checkout_id',
        'external_payment_id',
        'external_invoice_id',
        'invoice_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeReadyToView(Builder $query): void
    {
        $query->whereIn('status', [OrderStatus::Cancelled, OrderStatus::Processing, OrderStatus::Succeeded]);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', OrderStatus::Succeeded);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => OrderStatus::class,
        ];
    }
}
