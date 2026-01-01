<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\ForumCategoryGroupCreated;
use App\Events\ForumCategoryGroupDeleted;
use App\Events\ForumCategoryGroupUpdated;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $category_id
 * @property int $group_id
 * @property int $read
 * @property int $write
 * @property int $delete
 * @property-read ForumCategory|null $category
 * @property-read Group $group
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereDelete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereWrite($value)
 *
 * @mixin \Eloquent
 */
class ForumCategoryGroup extends Pivot
{
    protected $table = 'forums_categories_groups';

    protected $dispatchesEvents = [
        'created' => ForumCategoryGroupCreated::class,
        'updated' => ForumCategoryGroupUpdated::class,
        'deleting' => ForumCategoryGroupDeleted::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'id', 'category_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
