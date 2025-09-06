<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $forum_id
 * @property int $group_id
 * @property-read Forum $forum
 * @property-read Group $group
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereForumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereGroupId($value)
 *
 * @mixin \Eloquent
 */
class ForumGroup extends Pivot
{
    protected $table = 'forums_groups';

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
