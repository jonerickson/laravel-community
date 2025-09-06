<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $category_id
 * @property int $group_id
 * @property-read ForumCategory|null $category
 * @property-read Group $group
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereGroupId($value)
 *
 * @mixin \Eloquent
 */
class ForumCategoryGroup extends Pivot
{
    protected $table = 'forums_categories_groups';

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'id', 'category_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
