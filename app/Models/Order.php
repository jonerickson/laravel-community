<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingReason;
use App\Enums\OrderStatus;
use App\Events\OrderSaved;
use App\Managers\PaymentManager;
use App\Traits\HasNotes;
use App\Traits\HasReferenceId;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Number;
use Override;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property int $id
 * @property string $reference_id
 * @property int $user_id
 * @property OrderStatus $status
 * @property BillingReason|null $billing_reason
 * @property float|null $amount_due
 * @property float|null $amount_overpaid
 * @property float|null $amount_paid
 * @property float|null $amount_remaining
 * @property string|null $refund_notes
 * @property string|null $refund_reason
 * @property string|null $invoice_number
 * @property string|null $invoice_url
 * @property string|null $external_invoice_id
 * @property string|null $external_checkout_id
 * @property string|null $external_order_id
 * @property string|null $external_payment_id
 * @property string|null $external_event_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int|float $amount
 * @property-read float $amount_subtotal
 * @property-read mixed $checkout_url
 * @property-read float $commission_amount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Commission> $commissions
 * @property-read int|null $commissions_count
 * @property-read OrderDiscount|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Discount> $discounts
 * @property-read int|null $discounts_count
 * @property-read bool $is_one_time
 * @property-read bool $is_recurring
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Note> $notes
 * @property-read int|null $notes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Price> $prices
 * @property-read int|null $prices_count
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 *
 * @method static Builder<static>|Order cancelled()
 * @method static Builder<static>|Order completed()
 * @method static \Database\Factories\OrderFactory factory($count = null, $state = [])
 * @method static Builder<static>|Order newModelQuery()
 * @method static Builder<static>|Order newQuery()
 * @method static Builder<static>|Order query()
 * @method static Builder<static>|Order readyToView()
 * @method static Builder<static>|Order refunded()
 * @method static Builder<static>|Order whereAmountDue($value)
 * @method static Builder<static>|Order whereAmountOverpaid($value)
 * @method static Builder<static>|Order whereAmountPaid($value)
 * @method static Builder<static>|Order whereAmountRemaining($value)
 * @method static Builder<static>|Order whereBillingReason($value)
 * @method static Builder<static>|Order whereCreatedAt($value)
 * @method static Builder<static>|Order whereExternalCheckoutId($value)
 * @method static Builder<static>|Order whereExternalEventId($value)
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
class Order extends Model implements HasLabel
{
    use HasFactory;
    use HasNotes;
    use HasReferenceId;
    use HasRelationships;

    protected $attributes = [
        'status' => OrderStatus::Pending,
    ];

    protected $fillable = [
        'user_id',
        'status',
        'billing_reason',
        'amount_due',
        'amount_overpaid',
        'amount_paid',
        'amount_remaining',
        'refund_reason',
        'refund_notes',
        'amount',
        'invoice_number',
        'external_order_id',
        'external_checkout_id',
        'external_payment_id',
        'external_invoice_id',
        'external_event_id',
        'invoice_url',
    ];

    protected $appends = [
        'amount',
        'amount_subtotal',
        'checkout_url',
        'is_recurring',
        'is_one_time',
        'commission_amount',
    ];

    protected $dispatchesEvents = [
        'saved' => OrderSaved::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function prices(): HasManyThrough
    {
        return $this->hasManyThrough(Price::class, OrderItem::class, 'order_id', 'id', 'id', 'price_id');
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'orders_discounts')
            ->withPivot('amount_applied', 'balance_before', 'balance_after', 'external_discount_id')
            ->withTimestamps()
            ->using(OrderDiscount::class);
    }

    public function subscriptions(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->items(),
            (new OrderItem)->price(),
            (new Price)->subscriptions()
        );
    }

    public function checkoutUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->status->canCheckout()) {
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

    public function commissionAmount(): Attribute
    {
        return Attribute::get(fn (): float => (float) $this->commissions->sum('amount'))
            ->shouldCache();
    }

    public function amount(): Attribute
    {
        return Attribute::get(function ($value, array $attributes): int|float {
            if (isset($attributes['amount_paid'])) {
                return $attributes['amount_paid'] / 100;
            }

            $subtotal = $this->amount_subtotal;
            $discountAmount = $this->discounts->sum('pivot.amount_applied');

            return $subtotal - $discountAmount;
        })->shouldCache();
    }

    public function amountSubtotal(): Attribute
    {
        return Attribute::get(fn (): float => $this->items->sum('amount'))
            ->shouldCache();
    }

    public function amountDue(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?float => filled($value) ? (float) $value / 100 : null,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    public function amountOverpaid(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?float => filled($value) ? (float) $value / 100 : null,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    public function amountPaid(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?float => filled($value) ? (float) $value / 100 : null,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    public function amountRemaining(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?float => filled($value) ? (float) $value / 100 : null,
            set: fn (float $value): int => (int) ($value * 100),
        );
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

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', OrderStatus::Cancelled);
    }

    public function scopeRefunded(Builder $query): void
    {
        $query->where('status', OrderStatus::Refunded);
    }

    public function getLabel(): string
    {
        return sprintf('%s - %s - %s - %s', $this->reference_id, $this->user->name, Number::currency($this->amount), $this->status->getLabel());
    }

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Order $order): void {
            $order->notes()->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'billing_reason' => BillingReason::class,
            'amount_due' => 'integer',
            'amount_overpaid' => 'integer',
            'amount_paid' => 'integer',
            'amount_remaining' => 'integer',
        ];
    }
}
