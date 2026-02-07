<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PolicyConsentContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $policy_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $fingerprint_id
 * @property PolicyConsentContext $context
 * @property \Illuminate\Support\Carbon $consented_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Policy $policy
 * @property-read User $user
 *
 * @method static \Database\Factories\PolicyConsentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereConsentedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereFingerprintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent wherePolicyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyConsent whereUserId($value)
 *
 * @mixin \Eloquent
 */
class PolicyConsent extends Model
{
    use HasFactory;

    protected $table = 'policy_consents';

    protected $fillable = [
        'user_id',
        'policy_id',
        'ip_address',
        'user_agent',
        'fingerprint_id',
        'context',
        'consented_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'context' => PolicyConsentContext::class,
        ];
    }
}
