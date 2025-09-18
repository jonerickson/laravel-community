<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionInterval;
use App\Events\ProductPriceCreated;
use App\Events\ProductPriceDeleted;
use App\Events\ProductPriceDeleting;
use App\Events\ProductPriceUpdated;
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
 * @property int $amount
 * @property string $currency
 * @property SubscriptionInterval|null $interval
 * @property int $interval_count
 * @property bool $is_active
 * @property bool $is_default
 * @property string|null $external_price_id
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 *
 * @method static Builder<static>|Price active()
 * @method static Builder<static>|Price default()
 * @method static Builder<static>|Price inactive()
 * @method static Builder<static>|Price newModelQuery()
 * @method static Builder<static>|Price newQuery()
 * @method static Builder<static>|Price oneTime()
 * @method static Builder<static>|Price query()
 * @method static Builder<static>|Price recurring()
 * @method static Builder<static>|Price whereAmount($value)
 * @method static Builder<static>|Price whereCreatedAt($value)
 * @method static Builder<static>|Price whereCurrency($value)
 * @method static Builder<static>|Price whereDescription($value)
 * @method static Builder<static>|Price whereExternalPriceId($value)
 * @method static Builder<static>|Price whereId($value)
 * @method static Builder<static>|Price whereInterval($value)
 * @method static Builder<static>|Price whereIntervalCount($value)
 * @method static Builder<static>|Price whereIsActive($value)
 * @method static Builder<static>|Price whereIsDefault($value)
 * @method static Builder<static>|Price whereMetadata($value)
 * @method static Builder<static>|Price whereName($value)
 * @method static Builder<static>|Price whereProductId($value)
 * @method static Builder<static>|Price whereUpdatedAt($value)
 * @method static Builder<static>|Price withExternalPrice()
 * @method static Builder<static>|Price withoutExternalPrice()
 *
 * @mixin \Eloquent
 */
class Price extends Model
{
    use Activateable;
    use HasFactory;
    use HasMetadata;

    protected $fillable = [
        'product_id',
        'name',
        'amount',
        'currency',
        'interval',
        'interval_count',
        'external_price_id',
        'is_default',
        'description',
    ];

    protected $hidden = [
        'external_price_id',
    ];

    protected $touches = [
        'product',
    ];

    protected $dispatchesEvents = [
        'created' => ProductPriceCreated::class,
        'updated' => ProductPriceUpdated::class,
        'deleting' => ProductPriceDeleting::class,
        'deleted' => ProductPriceDeleted::class,
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

    public function scopeWithExternalPrice(Builder $query): void
    {
        $query->whereNotNull('external_price_id');
    }

    public function scopeWithoutExternalPrice(Builder $query): void
    {
        $query->whereNull('external_price_id');
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
        return ! is_null($this->external_price_id);
    }

    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2);
    }

    public function getDisplayName(): string
    {
        if ($this->isRecurring()) {
            $interval = $this->interval_count > 1
                ? "$this->interval_count {$this->interval}s"
                : $this->interval;

            return "$this->name (per $interval)";
        }

        return $this->name;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'interval' => SubscriptionInterval::class,
            'is_default' => 'boolean',
        ];
    }
}
