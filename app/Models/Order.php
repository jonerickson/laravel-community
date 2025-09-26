<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Managers\PaymentManager;
use App\Observers\OrderObserver;
use App\Traits\HasReferenceId;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 * @property-read mixed $is_one_time
 * @property-read mixed $is_recurring
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
#[ObservedBy(OrderObserver::class)]
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

    public static function boot(): void
    {
        parent::boot();

        static::saving(function (Order $model) {
            if (blank($model->name)) {
                $model->forceFill([
                    'name' => $model->items?->first?->product?->name,
                ]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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

            if ($this->is_one_time) {
                return app(PaymentManager::class)->getCheckoutUrl(
                    user: $this->user,
                    order: $this
                );
            }

            return app(PaymentManager::class)->startSubscription(
                user: $this->user,
                order: $this
            );
        })->shouldCache();
    }

    public function isRecurring(): Attribute
    {
        return Attribute::get(fn () => filled($this->items->firstWhere(fn (OrderItem $orderItem) => $orderItem->price->is_recurring)));
    }

    public function isOneTime(): Attribute
    {
        return Attribute::get(fn () => ! $this->is_recurring);
    }

    public function scopeReadyToView(Builder $query): void
    {
        $query->whereIn('status', [OrderStatus::Cancelled, OrderStatus::Pending, OrderStatus::Succeeded, OrderStatus::Refunded]);
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
