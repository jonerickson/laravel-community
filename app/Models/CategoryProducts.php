<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int|null $category_id
 * @property int|null $product_id
 * @property-read ProductCategory|null $category
 * @property-read Product|null $product
 *
 * @method static Builder<static>|CategoryProducts newModelQuery()
 * @method static Builder<static>|CategoryProducts newQuery()
 * @method static Builder<static>|CategoryProducts query()
 * @method static Builder<static>|CategoryProducts whereCategoryId($value)
 * @method static Builder<static>|CategoryProducts whereProductId($value)
 *
 * @mixin \Eloquent
 */
class CategoryProducts extends Pivot
{
    protected $table = 'categories_products';

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
