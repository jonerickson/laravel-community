<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\UserSocialCreated;
use App\Events\UserSocialDeleted;
use App\Traits\HasLogging;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User $user
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
    use HasLogging;

    protected $table = 'users_socials';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_name',
        'provider_email',
        'provider_avatar',
    ];

    protected $dispatchesEvents = [
        'created' => UserSocialCreated::class,
        'deleting' => UserSocialDeleted::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLoggedAttributes(): array
    {
        return [
            'provider',
            'provider_id',
            'provider_name',
            'provider_email',
            'provider_avatar',
        ];
    }

    public function getActivityDescription(string $eventName): string
    {
        return "User integration {$eventName}";
    }

    public function getActivityLogName(): string
    {
        return 'auth';
    }
}
