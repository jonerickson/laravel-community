<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string|null $description
 * @property numeric $amount
 * @property string $currency
 * @property string|null $interval
 * @property int $interval_count
 * @property bool $is_active
 * @property bool $is_default
 * @property string|null $stripe_price_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Product $product
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice default()
 * @method static \Database\Factories\ProductPriceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice oneTime()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice recurring()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereIntervalCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereIsDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereStripePriceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice withStripePrice()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice withoutStripePrice()
 *
 * @mixin \Eloquent
 */
class ProductPrice extends Model
{
    use HasFactory;
    use HasMetadata;

    protected $fillable = [
        'product_id',
        'name',
        'amount',
        'currency',
        'interval',
        'interval_count',
        'stripe_price_id',
        'is_active',
        'is_default',
        'description',
    ];

    protected $hidden = [
        'stripe_price_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeRecurring($query)
    {
        return $query->whereNotNull('interval');
    }

    public function scopeOneTime($query)
    {
        return $query->whereNull('interval');
    }

    public function scopeWithStripePrice($query)
    {
        return $query->whereNotNull('stripe_price_id');
    }

    public function scopeWithoutStripePrice($query)
    {
        return $query->whereNull('stripe_price_id');
    }

    public function isRecurring(): bool
    {
        return ! is_null($this->interval);
    }

    public function isOneTime(): bool
    {
        return is_null($this->interval);
    }

    public function hasStripePrice(): bool
    {
        return ! is_null($this->stripe_price_id);
    }

    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2);
    }

    public function getDisplayName(): string
    {
        if ($this->isRecurring()) {
            $interval = $this->interval_count > 1
                ? "{$this->interval_count} {$this->interval}s"
                : $this->interval;

            return "{$this->name} (per {$interval})";
        }

        return $this->name;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }
}
