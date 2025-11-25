<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\UserGroupCreated;
use App\Events\UserGroupDeleted;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $user_id
 * @property int $group_id
 * @property-read Group $group
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGroup whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserGroup whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserGroup extends Pivot
{
    protected $table = 'users_groups';

    protected $fillable = [
        'user_id',
        'group_id',
    ];

    protected $dispatchesEvents = [
        'created' => UserGroupCreated::class,
        'deleting' => UserGroupDeleted::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
