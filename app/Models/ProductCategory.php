<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Sluggable;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Database\Factories\ProductCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProductCategory extends Model implements Sluggable
{
    use HasFactory;
    use HasSlug;

    protected $table = 'products_categories';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function generateSlug(): string
    {
        return Str::slug($this->name);
    }
}
