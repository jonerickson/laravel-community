<?php

declare(strict_types=1);

namespace App\Models;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use App\Events\UserIntegrationCreated;
use App\Events\UserIntegrationDeleted;
use App\Traits\HasLogging;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_id
 * @property string|null $provider_name
 * @property string|null $provider_email
 * @property string|null $provider_avatar
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereLastSyncedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereProviderAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereProviderEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereProviderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[ApiResource(
    operations: [
        new Get(uriTemplate: 'integrations/{id}', openapi: false),
    ],
    normalizationContext: [
        AbstractItemNormalizer::GROUPS => ['integration'],
    ]
)]
#[ApiProperty(property: 'provider', serialize: new Groups(['user', 'integration']))]
#[ApiProperty(property: 'providerId', serialize: new Groups(['user', 'integration']))]
#[ApiProperty(property: 'providerName', serialize: new Groups(['user', 'integration']))]
#[ApiProperty(property: 'providerEmail', serialize: new Groups(['user', 'integration']))]
#[ApiProperty(property: 'providerAvatar', serialize: new Groups(['user', 'integration']))]
class UserIntegration extends Model
{
    use HasLogging;

    protected $table = 'users_integrations';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_name',
        'provider_email',
        'provider_avatar',
        'access_token',
        'refresh_token',
        'expires_at',
        'last_synced_at',
    ];

    protected $dispatchesEvents = [
        'created' => UserIntegrationCreated::class,
        'deleting' => UserIntegrationDeleted::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<int, string>
     */
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
        return 'User integration '.$eventName;
    }

    public function getActivityLogName(): string
    {
        return 'auth';
    }

    #[Override]
    protected static function booted(): void
    {
        static::creating(function (UserIntegration $model): void {
            $model->fill([
                'last_synced_at' => now(),
            ]);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }
}
