<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial query()
 *
 * @mixin \Eloquent
 */
class UserSocial extends Model
{
    protected $table = 'users_socials';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_name',
        'provider_email',
        'provider_avatar',
    ];
}
