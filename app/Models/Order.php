<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Events\OrderSaving;
use App\Managers\PaymentManager;
use App\Traits\HasReferenceId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $reference_id
 * @property int $user_id
 * @property OrderStatus $status
 * @property string|null $refund_notes
 * @property string|null $refund_reason
 * @property string|null $invoice_number
 * @property string|null $invoice_url
 * @property string|null $external_invoice_id
 * @property string|null $external_checkout_id
 * @property string|null $external_order_id
 * @property string|null $external_payment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $amount
 * @property-read mixed $checkout_url
 * @property-read bool $is_one_time
 * @property-read bool $is_recurring
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read User $user
 *
 * @method static Builder<static>|Order completed()
 * @method static \Database\Factories\OrderFactory factory($count = null, $state = [])
 * @method static Builder<static>|Order newModelQuery()
 * @method static Builder<static>|Order newQuery()
 * @method static Builder<static>|Order query()
 * @method static Builder<static>|Order readyToView()
 * @method static Builder<static>|Order whereCreatedAt($value)
 * @method static Builder<static>|Order whereExternalCheckoutId($value)
 * @method static Builder<static>|Order whereExternalInvoiceId($value)
 * @method static Builder<static>|Order whereExternalOrderId($value)
 * @method static Builder<static>|Order whereExternalPaymentId($value)
 * @method static Builder<static>|Order whereId($value)
 * @method static Builder<static>|Order whereInvoiceNumber($value)
 * @method static Builder<static>|Order whereInvoiceUrl($value)
 * @method static Builder<static>|Order whereReferenceId($value)
 * @method static Builder<static>|Order whereRefundNotes($value)
 * @method static Builder<static>|Order whereRefundReason($value)
 * @method static Builder<static>|Order whereStatus($value)
 * @method static Builder<static>|Order whereUpdatedAt($value)
 * @method static Builder<static>|Order whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Order extends Model
{
    use HasFactory;
    use HasReferenceId;

    protected $attributes = [
        'status' => OrderStatus::Pending,
    ];

    protected $fillable = [
        'user_id',
        'status',
        'refund_reason',
        'refund_notes',
        'amount',
        'invoice_number',
        'external_order_id',
        'external_checkout_id',
        'external_payment_id',
        'external_invoice_id',
        'invoice_url',
    ];

    protected $appends = [
        'amount',
        'checkout_url',
        'is_recurring',
        'is_one_time',
    ];

    protected $dispatchesEvents = [
        'saving' => OrderSaving::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(Product::class, OrderItem::class, 'order_id', 'id', 'id', 'product_id');
    }

    public function amount(): Attribute
    {
        return Attribute::get(fn () => $this->items->sum('amount'))
            ->shouldCache();
    }

    public function checkoutUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->status->canCheckout()) {
                return null;
            }

            if ($this->is_recurring) {
                return null;
            }

            return rescue(fn () => app(PaymentManager::class)->getCheckoutUrl(
                order: $this
            ));
        })->shouldCache();
    }

    public function isRecurring(): Attribute
    {
        return Attribute::get(fn (): bool => filled($this->items->filter->price->firstWhere(fn (OrderItem $orderItem) => $orderItem->price->is_recurring)))
            ->shouldCache();
    }

    public function isOneTime(): Attribute
    {
        return Attribute::get(fn (): bool => ! $this->is_recurring)
            ->shouldCache();
    }

    public function scopeReadyToView(Builder $query): void
    {
        $query->whereIn('status', [OrderStatus::Cancelled, OrderStatus::Pending, OrderStatus::Succeeded, OrderStatus::Refunded])
            ->whereHas('items');
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', OrderStatus::Succeeded);
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }
}
