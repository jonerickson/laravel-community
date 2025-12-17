<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Traits\HasAuthor;
use App\Traits\HasMetadata;
use App\Traits\HasReferenceId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference_id
 * @property string $code
 * @property DiscountType $type
 * @property DiscountValueType $discount_type
 * @property float $value
 * @property float|null $current_balance
 * @property int|null $product_id
 * @property int|null $created_by
 * @property int|null $user_id
 * @property string|null $recipient_email
 * @property int|null $max_uses
 * @property int $times_used
 * @property float|null $min_order_amount
 * @property string|null $external_coupon_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read bool $can_be_used
 * @property-read User|null $creator
 * @property-read User|null $customer
 * @property-read bool $has_balance
 * @property-read bool $is_expired
 * @property-read bool $is_valid
 * @property-read OrderDiscount|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Order> $orders
 * @property-read int|null $orders_count
 * @property-read Product|null $product
 * @property-read mixed $value_label
 *
 * @method static Builder<static>|Discount active()
 * @method static Builder<static>|Discount byCode(string $code)
 * @method static Builder<static>|Discount expired()
 * @method static \Database\Factories\DiscountFactory factory($count = null, $state = [])
 * @method static Builder<static>|Discount giftCards()
 * @method static Builder<static>|Discount newModelQuery()
 * @method static Builder<static>|Discount newQuery()
 * @method static Builder<static>|Discount promoCodes()
 * @method static Builder<static>|Discount query()
 * @method static Builder<static>|Discount whereActivatedAt($value)
 * @method static Builder<static>|Discount whereCode($value)
 * @method static Builder<static>|Discount whereCreatedAt($value)
 * @method static Builder<static>|Discount whereCreatedBy($value)
 * @method static Builder<static>|Discount whereCurrentBalance($value)
 * @method static Builder<static>|Discount whereDiscountType($value)
 * @method static Builder<static>|Discount whereExpiresAt($value)
 * @method static Builder<static>|Discount whereExternalCouponId($value)
 * @method static Builder<static>|Discount whereId($value)
 * @method static Builder<static>|Discount whereMaxUses($value)
 * @method static Builder<static>|Discount whereMetadata($value)
 * @method static Builder<static>|Discount whereMinOrderAmount($value)
 * @method static Builder<static>|Discount whereProductId($value)
 * @method static Builder<static>|Discount whereRecipientEmail($value)
 * @method static Builder<static>|Discount whereReferenceId($value)
 * @method static Builder<static>|Discount whereTimesUsed($value)
 * @method static Builder<static>|Discount whereType($value)
 * @method static Builder<static>|Discount whereUpdatedAt($value)
 * @method static Builder<static>|Discount whereUserId($value)
 * @method static Builder<static>|Discount whereValue($value)
 * @method static Builder<static>|Discount withBalance()
 *
 * @mixin \Eloquent
 */
class Discount extends Model
{
    use HasAuthor;
    use HasFactory;
    use HasMetadata;
    use HasReferenceId;

    protected $fillable = [
        'code',
        'type',
        'discount_type',
        'value',
        'current_balance',
        'product_id',
        'user_id',
        'recipient_email',
        'max_uses',
        'times_used',
        'min_order_amount',
        'external_coupon_id',
        'expires_at',
        'activated_at',
    ];

    protected $attributes = [
        'times_used' => 0,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'orders_discounts')
            ->withPivot('amount_applied', 'balance_before', 'balance_after', 'external_discount_id')
            ->withTimestamps()
            ->using(OrderDiscount::class);
    }

    public function isExpired(): Attribute
    {
        return Attribute::get(fn (): bool => filled($this->expires_at) && $this->expires_at->isPast());
    }

    public function hasBalance(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->type !== DiscountType::GiftCard) {
                return true;
            }

            return ($this->current_balance ?? 0) > 0;
        });
    }

    public function isValid(): Attribute
    {
        return Attribute::get(function (): bool {
            if ($this->is_expired) {
                return false;
            }

            if (! $this->has_balance) {
                return false;
            }

            return ! (filled($this->max_uses) && $this->times_used >= $this->max_uses);
        });
    }

    public function canBeUsed(): Attribute
    {
        return Attribute::get(fn (): bool => $this->is_valid);
    }

    public function generateCode(?DiscountType $type = null): string
    {
        $prefix = match ($type ?? $this->type) {
            DiscountType::GiftCard => 'GIFT',
            DiscountType::PromoCode => 'PROMO',
            DiscountType::Manual => 'MANUAL',
            DiscountType::Cancellation => 'CANCELLATION-OFFER',
        };

        return Str::upper($prefix.'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where(function (Builder $q): void {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired(Builder $query): void
    {
        $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeGiftCards(Builder $query): void
    {
        $query->where('type', DiscountType::GiftCard);
    }

    public function scopePromoCodes(Builder $query): void
    {
        $query->where('type', DiscountType::PromoCode);
    }

    public function scopeWithBalance(Builder $query): void
    {
        $query->where(function (Builder $q): void {
            $q->where('type', '!=', DiscountType::GiftCard)
                ->orWhere('current_balance', '>', 0);
        });
    }

    public function scopeByCode(Builder $query, string $code): void
    {
        $query->where('code', Str::upper($code));
    }

    public function getValueAttribute($value): float
    {
        return $value / 100;
    }

    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = (int) $value * 100;
    }

    public function valueLabel(): Attribute
    {
        return Attribute::get(fn () => match ($this->discount_type) {
            DiscountValueType::Percentage => Number::percentage($this->value),
            DiscountValueType::Fixed => Number::currency($this->value),
        });
    }

    public function currentBalance(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?float => filled($value) ? (float) $value / 100 : null,
            set: fn (?float $value): ?int => filled($value) ? (int) ($value * 100) : null,
        );
    }

    public function minOrderAmount(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): ?float => filled($value) ? (float) $value / 100 : null,
            set: fn (?float $value): ?int => filled($value) ? (int) ($value * 100) : null,
        );
    }

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'discount_type' => DiscountValueType::class,
            'value' => 'integer',
            'current_balance' => 'integer',
            'max_uses' => 'integer',
            'times_used' => 'integer',
            'min_order_amount' => 'integer',
            'expires_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }
}
