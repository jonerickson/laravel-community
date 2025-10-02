<?php

declare(strict_types=1);

namespace App\Models;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Contracts\Sluggable;
use App\Enums\ProductTaxCode;
use App\Enums\ProductType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Traits\Featureable;
use App\Traits\HasFeaturedImage;
use App\Traits\HasFiles;
use App\Traits\HasGroups;
use App\Traits\HasLogging;
use App\Traits\HasMetadata;
use App\Traits\HasReferenceId;
use App\Traits\HasSlug;
use App\Traits\LogsMarketplaceActivity;
use App\Traits\Reviewable;
use App\Traits\Trendable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @property int $id
 * @property string $reference_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property ProductType $type
 * @property ProductTaxCode|null $tax_code
 * @property bool $is_featured
 * @property bool $is_subscription_only
 * @property int $trial_days
 * @property bool $allow_promotion_codes
 * @property string|null $featured_image
 * @property string|null $external_product_id
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Price> $activePrices
 * @property-read int|null $active_prices_count
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read int|float $average_rating
 * @property-read Collection<int, ProductCategory> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 * @property-read Price|null $defaultPrice
 * @property-read string|null $featured_image_url
 * @property-read File|null $file
 * @property-read Collection<int, File> $files
 * @property-read int|null $files_count
 * @property-read Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read int|null $order_items_count
 * @property-read Collection<int, Policy> $policies
 * @property-read int|null $policies_count
 * @property-read Collection<int, Price> $prices
 * @property-read int|null $prices_count
 * @property-read Collection<int, Comment> $reviews
 * @property-read int|null $reviews_count
 * @property-read float $trending_score
 *
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|Product featured()
 * @method static Builder<static>|Product hotTopics(?int $limit = null)
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product products()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product risingTopics(?int $limit = null)
 * @method static Builder<static>|Product subscriptions()
 * @method static Builder<static>|Product trending(?int $limit = null, ?\Illuminate\Support\Carbon $referenceTime = null)
 * @method static Builder<static>|Product trendingInTimeframe(string $timeframe = 'week', ?int $limit = null)
 * @method static Builder<static>|Product whereAllowPromotionCodes($value)
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereExternalProductId($value)
 * @method static Builder<static>|Product whereFeaturedImage($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereIsFeatured($value)
 * @method static Builder<static>|Product whereIsSubscriptionOnly($value)
 * @method static Builder<static>|Product whereMetadata($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product whereReferenceId($value)
 * @method static Builder<static>|Product whereSlug($value)
 * @method static Builder<static>|Product whereTaxCode($value)
 * @method static Builder<static>|Product whereTrialDays($value)
 * @method static Builder<static>|Product whereType($value)
 * @method static Builder<static>|Product whereUpdatedAt($value)
 * @method static Builder<static>|Product withExternalProduct()
 * @method static Builder<static>|Product withoutExternalProduct()
 *
 * @mixin \Eloquent
 */
#[ApiResource(
    operations: [
        new Get,
    ],
    normalizationContext: [
        AbstractItemNormalizer::GROUPS => ['product'],
        AbstractItemNormalizer::SKIP_NULL_VALUES => false,
    ]
)]
#[ApiProperty(identifier: true, property: 'id', serialize: new Groups(['user', 'product']))]
#[ApiProperty(property: 'referenceId', serialize: new Groups(['user', 'product']))]
#[ApiProperty(property: 'name', serialize: new Groups(['user', 'product']))]
class Product extends Model implements Sluggable
{
    use Featureable;
    use HasFactory;
    use HasFeaturedImage;
    use HasFiles;
    use HasGroups;
    use HasLogging;
    use HasMetadata;
    use HasReferenceId;
    use HasSlug;
    use LogsMarketplaceActivity;
    use Reviewable;
    use Searchable;
    use Trendable;

    protected $attributes = [
        'allow_promotion_codes' => false,
        'trial_days' => 0,
    ];

    protected $fillable = [
        'name',
        'description',
        'type',
        'tax_code',
        'is_subscription_only',
        'allow_promotion_codes',
        'trial_days',
        'external_product_id',
    ];

    protected $dispatchesEvents = [
        'created' => ProductCreated::class,
        'updated' => ProductUpdated::class,
        'deleting' => ProductDeleted::class,
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'categories_products', 'product_id', 'category_id');
    }

    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(Policy::class, 'policies_products');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function activePrices(): HasMany
    {
        return $this->prices()->active();
    }

    public function defaultPrice(): HasOne
    {
        return $this->hasOne(Price::class)->ofMany([
            'id' => 'max',
        ], function (Builder|Price $query): void {
            $query->default()->active();
        });
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }

    public function scopeProducts(Builder $query): void
    {
        $query->where('type', ProductType::Product);
    }

    public function scopeSubscriptions(Builder $query): void
    {
        $query->where('type', ProductType::Subscription);
    }

    public function isProduct(): bool
    {
        return $this->type === ProductType::Product;
    }

    public function isSubscription(): bool
    {
        return $this->type === ProductType::Subscription;
    }

    public function hasExternalProduct(): bool
    {
        return ! is_null($this->external_product_id);
    }

    public function scopeWithExternalProduct(Builder $query): void
    {
        $query->whereNotNull('external_product_id');
    }

    public function scopeWithoutExternalProduct(Builder $query): void
    {
        $query->whereNull('external_product_id');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value ?? '',
        ];
    }

    public function getLoggedAttributes(): array
    {
        return [
            'name',
            'description',
            'type',
            'is_featured',
            'allow_promotion_codes',
            'trial_days',
            'external_product_id',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        $type = $this->type?->value ?? 'product';

        return ucfirst($type)." $this->name $eventName";
    }

    public function getActivityLogName(): string
    {
        return 'store';
    }

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'tax_code' => ProductTaxCode::class,
            'is_subscription_only' => 'boolean',
            'allow_promotion_codes' => 'boolean',
            'trial_days' => 'integer',
        ];
    }
}
