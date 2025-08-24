<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Enums\ProductType;
use App\Traits\HasFeaturedImage;
use App\Traits\HasFiles;
use App\Traits\HasLogging;
use App\Traits\HasMetadata;
use App\Traits\HasReviews;
use App\Traits\HasSlug;
use App\Traits\LogsMarketplaceActivity;
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

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property ProductType $type
 * @property int $is_featured
 * @property string|null $featured_image
 * @property string|null $stripe_product_id
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ProductPrice> $activePrices
 * @property-read int|null $active_prices_count
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read mixed $average_rating
 * @property-read Collection<int, ProductCategory> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, Policy> $policies
 * @property-read int|null $policies_count
 * @property-read Collection<int, Comment> $comments
 * @property-read int|null $comments_count
 * @property-read ProductPrice|null $defaultPrice
 * @property-read string|null $featured_image_url
 * @property-read File|null $file
 * @property-read Collection<int, File> $files
 * @property-read int|null $files_count
 * @property-read Collection<int, ProductPrice> $prices
 * @property-read int|null $prices_count
 * @property-read Collection<int, Comment> $reviews
 * @property-read int|null $reviews_count
 *
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|Product featured()
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product products()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product subscriptions()
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereFeaturedImage($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereIsFeatured($value)
 * @method static Builder<static>|Product whereMetadata($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product whereSlug($value)
 * @method static Builder<static>|Product whereStripeProductId($value)
 * @method static Builder<static>|Product whereType($value)
 * @method static Builder<static>|Product whereUpdatedAt($value)
 * @method static Builder<static>|Product withStripeProduct()
 * @method static Builder<static>|Product withoutStripeProduct()
 *
 * @mixin \Eloquent
 */
class Product extends Model implements Sluggable
{
    use HasFactory;
    use HasFeaturedImage;
    use HasFiles;
    use HasLogging;
    use HasMetadata;
    use HasReviews;
    use HasSlug;
    use LogsMarketplaceActivity;
    use Searchable;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_featured',
        'stripe_product_id',
        'files',
    ];

    protected $hidden = [
        'stripe_product_id',
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
        return $this->hasMany(ProductPrice::class);
    }

    public function activePrices(): HasMany
    {
        return $this->prices()->active();
    }

    public function defaultPrice(): HasOne
    {
        return $this->hasOne(ProductPrice::class)->ofMany([
            'id' => 'max',
        ], function (Builder|ProductPrice $query) {
            $query->default()->active();
        });
    }

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }

    public function scopeProducts($query)
    {
        return $query->where('type', ProductType::Product);
    }

    public function scopeSubscriptions($query)
    {
        return $query->where('type', ProductType::Subscription);
    }

    public function isProduct(): bool
    {
        return $this->type === ProductType::Product;
    }

    public function isSubscription(): bool
    {
        return $this->type === ProductType::Subscription;
    }

    public function hasStripeProduct(): bool
    {
        return ! is_null($this->stripe_product_id);
    }

    public function scopeWithStripeProduct($query)
    {
        return $query->whereNotNull('stripe_product_id');
    }

    public function scopeWithoutStripeProduct($query)
    {
        return $query->whereNull('stripe_product_id');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
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
            'stripe_product_id',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        $type = $this->type?->value ?? 'product';
        $name = $this->name ? " \"{$this->name}\"" : '';

        return ucfirst($type).$name." {$eventName}";
    }

    public function getActivityLogName(): string
    {
        return 'ecommerce';
    }

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
        ];
    }
}
