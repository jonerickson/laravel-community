<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\ProductApprovalStatus;
use App\Enums\ProductTaxCode;
use App\Enums\ProductType;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Traits\Activateable;
use App\Traits\Featureable;
use App\Traits\HasFeaturedImage;
use App\Traits\HasFiles;
use App\Traits\HasGroups;
use App\Traits\HasImages;
use App\Traits\HasInventory;
use App\Traits\HasLogging;
use App\Traits\HasMetadata;
use App\Traits\HasReferenceId;
use App\Traits\HasSlug;
use App\Traits\LogsStoreActivity;
use App\Traits\Orderable;
use App\Traits\Reviewable;
use App\Traits\Searchable;
use App\Traits\Trendable;
use App\Traits\Visible;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property ProductType $type
 * @property int|null $seller_id
 * @property numeric $commission_rate
 * @property string|null $rejection_reason
 * @property ProductTaxCode|null $tax_code
 * @property bool $is_featured
 * @property bool $is_active
 * @property bool $is_visible
 * @property bool $is_subscription_only
 * @property int $trial_days
 * @property bool $allow_promotion_codes
 * @property bool $allow_discount_codes
 * @property string|null $featured_image
 * @property int $order
 * @property string|null $external_product_id
 * @property ProductApprovalStatus $approval_status
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Price> $activePrices
 * @property-read int|null $active_prices_count
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Collection<int, Comment> $approvedReviews
 * @property-read int|null $approved_reviews_count
 * @property-read User|null $approver
 * @property-read int|float $average_rating
 * @property-read Collection<int, ProductCategory> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 * @property-read Price|null $defaultPrice
 * @property-read \App\Data\GroupStyleData|null $display_style
 * @property-read string|null $featured_image_url
 * @property-read File|null $file
 * @property-read Collection<int, File> $files
 * @property-read int|null $files_count
 * @property-read Collection<int, Group> $groups
 * @property-read int|null $groups_count
 * @property-read Image|null $image
 * @property-read Collection<int, Image> $images
 * @property-read int|null $images_count
 * @property-read InventoryItem|null $inventoryItem
 * @property-read Collection<int, InventoryTransaction> $inventoryTransactions
 * @property-read int|null $inventory_transactions_count
 * @property-read bool $is_marketplace_product
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read int|null $order_items_count
 * @property-read Collection<int, Policy> $policies
 * @property-read int|null $policies_count
 * @property-read Collection<int, Price> $prices
 * @property-read int|null $prices_count
 * @property-read Collection<int, Comment> $reviews
 * @property-read int|null $reviews_count
 * @property-read User|null $seller
 * @property-read float $trending_score
 *
 * @method static Builder<static>|Product active()
 * @method static Builder<static>|Product approved()
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|Product featured()
 * @method static Builder<static>|Product hidden()
 * @method static Builder<static>|Product hotTopics(?int $limit = null)
 * @method static Builder<static>|Product inactive()
 * @method static Builder<static>|Product marketplace()
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product notFeatured()
 * @method static Builder<static>|Product ordered()
 * @method static Builder<static>|Product pending()
 * @method static Builder<static>|Product products()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product rejected()
 * @method static Builder<static>|Product risingTopics(?int $limit = null)
 * @method static Builder<static>|Product subscriptions()
 * @method static Builder<static>|Product trending(?int $limit = null, ?\Illuminate\Support\Carbon $referenceTime = null)
 * @method static Builder<static>|Product trendingInTimeframe(string $timeframe = 'week', ?int $limit = null)
 * @method static Builder<static>|Product visible()
 * @method static Builder<static>|Product whereAllowDiscountCodes($value)
 * @method static Builder<static>|Product whereAllowPromotionCodes($value)
 * @method static Builder<static>|Product whereApprovalStatus($value)
 * @method static Builder<static>|Product whereApprovedAt($value)
 * @method static Builder<static>|Product whereApprovedBy($value)
 * @method static Builder<static>|Product whereCommissionRate($value)
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereExternalProductId($value)
 * @method static Builder<static>|Product whereFeaturedImage($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereIsActive($value)
 * @method static Builder<static>|Product whereIsFeatured($value)
 * @method static Builder<static>|Product whereIsSubscriptionOnly($value)
 * @method static Builder<static>|Product whereIsVisible($value)
 * @method static Builder<static>|Product whereMetadata($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product whereOrder($value)
 * @method static Builder<static>|Product whereReferenceId($value)
 * @method static Builder<static>|Product whereRejectionReason($value)
 * @method static Builder<static>|Product whereSellerId($value)
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
class Product extends Model implements HasLabel, Sluggable
{
    use Activateable;
    use Featureable;
    use HasFactory;
    use HasFeaturedImage;
    use HasFiles;
    use HasGroups;
    use HasImages;
    use HasInventory;
    use HasLogging;
    use HasMetadata;
    use HasReferenceId;
    use HasSlug;
    use LogsStoreActivity;
    use Orderable;
    use Reviewable;
    use Searchable;
    use Trendable;
    use Visible;

    protected $attributes = [
        'type' => ProductType::Product,
        'allow_promotion_codes' => false,
        'trial_days' => 0,
        'approval_status' => ProductApprovalStatus::Approved,
        'commission_rate' => 0,
    ];

    protected $fillable = [
        'seller_id',
        'approval_status',
        'commission_rate',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'name',
        'description',
        'type',
        'tax_code',
        'is_subscription_only',
        'allow_promotion_codes',
        'allow_discount_codes',
        'trial_days',
        'external_product_id',
    ];

    protected $dispatchesEvents = [
        'created' => ProductCreated::class,
        'updated' => ProductUpdated::class,
        'deleting' => ProductDeleted::class,
    ];

    protected $appends = [
        'is_marketplace_product',
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

    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, Price::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeMarketplace(Builder $query): void
    {
        $query->whereNotNull('seller_id');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('approval_status', ProductApprovalStatus::Approved);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('approval_status', ProductApprovalStatus::Pending);
    }

    public function scopeRejected(Builder $query): void
    {
        $query->where('approval_status', ProductApprovalStatus::Rejected);
    }

    public function isMarketplaceProduct(): Attribute
    {
        return Attribute::get(fn (): bool => ! is_null($this->seller_id))
            ->shouldCache();
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'categories' => $this->categories->pluck('name')->implode(', '),
            'type' => $this->type->value ?? '',
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return Gate::check('view', $this);
    }

    /**
     * @return array<int, string>
     */
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

        return ucfirst($type).sprintf(' %s %s', $this->name, $eventName);
    }

    public function getActivityLogName(): string
    {
        return 'store';
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    protected function casts(): array
    {
        return [
            'approval_status' => ProductApprovalStatus::class,
            'commission_rate' => 'decimal:2',
            'approved_at' => 'datetime',
            'type' => ProductType::class,
            'tax_code' => ProductTaxCode::class,
            'is_subscription_only' => 'boolean',
            'allow_promotion_codes' => 'boolean',
            'allow_discount_codes' => 'boolean',
            'trial_days' => 'integer',
        ];
    }
}
