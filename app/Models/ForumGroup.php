<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\ForumGroupCreated;
use App\Events\ForumGroupDeleted;
use App\Events\ForumGroupUpdated;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $forum_id
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
 * @property-read Forum $forum
 * @property-read Group $group
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereCreate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereDelete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereForumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereLock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereModerate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereMove($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup wherePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereReply($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereReport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ForumGroup whereUpdate($value)
 *
 * @mixin \Eloquent
 */
class ForumGroup extends Pivot
{
    protected $table = 'forums_groups';

    protected $dispatchesEvents = [
        'created' => ForumGroupCreated::class,
        'updated' => ForumGroupUpdated::class,
        'deleting' => ForumGroupDeleted::class,
    ];

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
