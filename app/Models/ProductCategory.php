<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasImages;
use App\Traits\HasLogging;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Image|null $image
 * @property-read Collection<int, Image> $images
 * @property-read int|null $images_count
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static \Database\Factories\ProductCategoryFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProductCategory newModelQuery()
 * @method static Builder<static>|ProductCategory newQuery()
 * @method static Builder<static>|ProductCategory query()
 * @method static Builder<static>|ProductCategory whereCreatedAt($value)
 * @method static Builder<static>|ProductCategory whereDescription($value)
 * @method static Builder<static>|ProductCategory whereId($value)
 * @method static Builder<static>|ProductCategory whereName($value)
 * @method static Builder<static>|ProductCategory whereSlug($value)
 * @method static Builder<static>|ProductCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProductCategory extends Model implements Sluggable
{
    use HasFactory;
    use HasImages;
    use HasLogging;
    use HasSlug;

    protected $table = 'products_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'categories_products', 'category_id', 'product_id');
    }

    public function generateSlug(): ?string
    {
        return Str::slug($this->name);
    }

    public function getLoggedAttributes(): array
    {
        return [
            'name',
            'description',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        $name = $this->name ? " \"{$this->name}\"" : '';

        return "Product category $name $eventName";
    }

    public function getActivityLogName(): string
    {
        return 'ecommerce';
    }
}
