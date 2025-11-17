<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\Activateable;
use App\Traits\HasFeaturedImage;
use App\Traits\HasLogging;
use App\Traits\HasSlug;
use App\Traits\Orderable;
use App\Traits\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $order
 * @property bool $is_active
 * @property bool $is_visible
 * @property int|null $parent_id
 * @property string $slug
 * @property string|null $featured_image
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Collection<int, ProductCategory> $children
 * @property-read int|null $children_count
 * @property-read string|null $featured_image_url
 * @property-read ProductCategory|null $parent
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static Builder<static>|ProductCategory active()
 * @method static \Database\Factories\ProductCategoryFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProductCategory hidden()
 * @method static Builder<static>|ProductCategory inactive()
 * @method static Builder<static>|ProductCategory newModelQuery()
 * @method static Builder<static>|ProductCategory newQuery()
 * @method static Builder<static>|ProductCategory ordered()
 * @method static Builder<static>|ProductCategory query()
 * @method static Builder<static>|ProductCategory visible()
 * @method static Builder<static>|ProductCategory whereCreatedAt($value)
 * @method static Builder<static>|ProductCategory whereDescription($value)
 * @method static Builder<static>|ProductCategory whereFeaturedImage($value)
 * @method static Builder<static>|ProductCategory whereId($value)
 * @method static Builder<static>|ProductCategory whereIsActive($value)
 * @method static Builder<static>|ProductCategory whereIsVisible($value)
 * @method static Builder<static>|ProductCategory whereName($value)
 * @method static Builder<static>|ProductCategory whereOrder($value)
 * @method static Builder<static>|ProductCategory whereParentId($value)
 * @method static Builder<static>|ProductCategory whereSlug($value)
 * @method static Builder<static>|ProductCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProductCategory extends Model implements Sluggable
{
    use Activateable;
    use HasFactory;
    use HasFeaturedImage;
    use HasLogging;
    use HasSlug;
    use Orderable;
    use Visible;

    protected $table = 'products_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'categories_products', 'category_id', 'product_id');
    }

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }

    /**
     * @return array<int, string>
     */
    public function getLoggedAttributes(): array
    {
        return [
            'name',
            'description',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        return sprintf('Product category %s %s', $this->name, $eventName);
    }

    public function getActivityLogName(): string
    {
        return 'store';
    }
}
