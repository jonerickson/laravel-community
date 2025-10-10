<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $group_id
 * @property string $discord_role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupDiscordRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupDiscordRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupDiscordRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupDiscordRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupDiscordRole whereDiscordRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupDiscordRole whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupDiscordRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupDiscordRole whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class GroupDiscordRole extends Model
{
    protected $table = 'groups_discord_roles';

    protected $fillable = [
        'discord_role_id',
    ];
}
