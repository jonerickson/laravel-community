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
 * @property int $create
 * @property int $read
 * @property int $update
 * @property int $delete
 * @property int $moderate
 * @property int $reply
 * @property int $report
 * @property int $pin
 * @property int $lock
 * @property int $move
 * @property-read ForumCategory|null $category
 * @property-read Group $group
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereCreate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereDelete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereLock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereModerate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereMove($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup wherePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereReply($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereReport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumCategoryGroup whereUpdate($value)
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
