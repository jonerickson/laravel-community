<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Activateable;
use App\Traits\HasMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 *
 * @method static Builder<static>|ProductPrice active()
 * @method static Builder<static>|ProductPrice default()
 * @method static \Database\Factories\ProductPriceFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProductPrice inactive()
 * @method static Builder<static>|ProductPrice newModelQuery()
 * @method static Builder<static>|ProductPrice newQuery()
 * @method static Builder<static>|ProductPrice oneTime()
 * @method static Builder<static>|ProductPrice query()
 * @method static Builder<static>|ProductPrice recurring()
 * @method static Builder<static>|ProductPrice whereAmount($value)
 * @method static Builder<static>|ProductPrice whereCreatedAt($value)
 * @method static Builder<static>|ProductPrice whereCurrency($value)
 * @method static Builder<static>|ProductPrice whereDescription($value)
 * @method static Builder<static>|ProductPrice whereId($value)
 * @method static Builder<static>|ProductPrice whereInterval($value)
 * @method static Builder<static>|ProductPrice whereIntervalCount($value)
 * @method static Builder<static>|ProductPrice whereIsActive($value)
 * @method static Builder<static>|ProductPrice whereIsDefault($value)
 * @method static Builder<static>|ProductPrice whereMetadata($value)
 * @method static Builder<static>|ProductPrice whereName($value)
 * @method static Builder<static>|ProductPrice whereProductId($value)
 * @method static Builder<static>|ProductPrice whereStripePriceId($value)
 * @method static Builder<static>|ProductPrice whereUpdatedAt($value)
 * @method static Builder<static>|ProductPrice withStripePrice()
 * @method static Builder<static>|ProductPrice withoutStripePrice()
 *
 * @mixin \Eloquent
 */
class ProductPrice extends Model
{
    use Activateable;
    use HasFactory;
    use HasMetadata;

    protected $table = 'products_prices';

    protected $fillable = [
        'product_id',
        'name',
        'amount',
        'currency',
        'interval',
        'interval_count',
        'stripe_price_id',
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

    public function scopeDefault(Builder $query): void
    {
        $query->where('is_default', true);
    }

    public function scopeRecurring(Builder $query): void
    {
        $query->whereNotNull('interval');
    }

    public function scopeOneTime(Builder $query): void
    {
        $query->whereNull('interval');
    }

    public function scopeWithStripePrice(Builder $query): void
    {
        $query->whereNotNull('stripe_price_id');
    }

    public function scopeWithoutStripePrice(Builder $query): void
    {
        $query->whereNull('stripe_price_id');
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
            'is_default' => 'boolean',
        ];
    }
}
