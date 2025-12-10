<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PriceType;
use App\Enums\SubscriptionInterval;
use App\Events\PriceCreated;
use App\Events\PriceDeleted;
use App\Events\PriceSaving;
use App\Events\PriceUpdated;
use App\Traits\Activateable;
use App\Traits\HasMetadata;
use App\Traits\HasReferenceId;
use App\Traits\Visible;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

/**
 * @property int $id
 * @property string $reference_id
 * @property int $product_id
 * @property string $name
 * @property string|null $description
 * @property PriceType|null $type
 * @property float $amount
 * @property string $currency
 * @property SubscriptionInterval|null $interval
 * @property int $interval_count
 * @property bool $is_active
 * @property bool $is_default
 * @property string|null $external_price_id
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $is_visible
 * @property-read bool $is_one_time
 * @property-read bool $is_recurring
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $orderItems
 * @property-read int|null $order_items_count
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 *
 * @method static Builder<static>|Price active()
 * @method static Builder<static>|Price default()
 * @method static \Database\Factories\PriceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Price hidden()
 * @method static Builder<static>|Price inactive()
 * @method static Builder<static>|Price newModelQuery()
 * @method static Builder<static>|Price newQuery()
 * @method static Builder<static>|Price oneTime()
 * @method static Builder<static>|Price query()
 * @method static Builder<static>|Price recurring()
 * @method static Builder<static>|Price visible()
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
 * @method static Builder<static>|Price whereIsVisible($value)
 * @method static Builder<static>|Price whereMetadata($value)
 * @method static Builder<static>|Price whereName($value)
 * @method static Builder<static>|Price whereProductId($value)
 * @method static Builder<static>|Price whereReferenceId($value)
 * @method static Builder<static>|Price whereType($value)
 * @method static Builder<static>|Price whereUpdatedAt($value)
 * @method static Builder<static>|Price withExternalPrice()
 * @method static Builder<static>|Price withoutExternalPrice()
 *
 * @mixin \Eloquent
 */
class Price extends Model implements HasLabel
{
    use Activateable;
    use HasFactory;
    use HasMetadata;
    use HasReferenceId;
    use Visible;

    protected $fillable = [
        'product_id',
        'name',
        'type',
        'amount',
        'currency',
        'interval',
        'interval_count',
        'external_price_id',
        'is_default',
        'description',
    ];

    protected $appends = [
        'is_recurring',
        'is_one_time',
    ];

    protected $hidden = [
        'external_price_id',
    ];

    protected $touches = [
        'product',
    ];

    protected $dispatchesEvents = [
        'saving' => PriceSaving::class,
        'created' => PriceCreated::class,
        'updated' => PriceUpdated::class,
        'deleting' => PriceDeleted::class,
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'stripe_price', 'external_price_id');
    }

    public function scopeDefault(Builder $query): void
    {
        $query->where('is_default', true);
    }

    public function scopeRecurring(Builder $query): void
    {
        $query->where('type', PriceType::Recurring);
    }

    public function scopeOneTime(Builder $query): void
    {
        $query->where('type', PriceType::OneTime);
    }

    public function scopeWithExternalPrice(Builder $query): void
    {
        $query->whereNotNull('external_price_id');
    }

    public function scopeWithoutExternalPrice(Builder $query): void
    {
        $query->whereNull('external_price_id');
    }

    public function isRecurring(): Attribute
    {
        return Attribute::get(fn (): bool => ! is_null($this->interval));
    }

    public function isOneTime(): Attribute
    {
        return Attribute::get(fn (): bool => ! $this->is_recurring);
    }

    public function getLabel(): string|Htmlable|null
    {
        $amount = Number::currency($this->amount);
        $interval = $this->interval?->getLabel();

        return Str::of($this->name)
            ->append(' - '.$amount)
            ->when(filled($interval), fn (Stringable $str): Stringable => $str->append(' / '.$interval))
            ->toString();
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): float => (float) $value / 100,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    public function toggleDefaultPrice(): void
    {
        $builder = static::query()
            ->whereKeyNot($this->id)
            ->where('product_id', $this->product_id);

        if ($builder->exists()) {
            $builder->update([
                'is_default' => false,
            ]);
        }
    }

    protected function casts(): array
    {
        return [
            'type' => PriceType::class,
            'amount' => 'integer',
            'interval' => SubscriptionInterval::class,
            'is_default' => 'boolean',
        ];
    }
}
