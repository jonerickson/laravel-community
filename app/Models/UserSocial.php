<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $provider_name
 * @property string|null $provider_email
 * @property string|null $provider_avatar
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereProviderAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereProviderEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereProviderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSocial whereUserId($value)
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
