<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\FingerprintCreated;
use App\Events\FingerprintUpdated;
use App\Traits\Blacklistable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $fingerprint_id
 * @property string|null $request_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int $suspect_score
 * @property Carbon|null $last_checked_at
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Blacklist|null $blacklist
 * @property-read bool $is_blacklisted
 * @property-read User|null $user
 *
 * @method static \Database\Factories\FingerprintFactory factory($count = null, $state = [])
 * @method static Builder<static>|Fingerprint newModelQuery()
 * @method static Builder<static>|Fingerprint newQuery()
 * @method static Builder<static>|Fingerprint query()
 * @method static Builder<static>|Fingerprint whereCreatedAt($value)
 * @method static Builder<static>|Fingerprint whereFingerprintId($value)
 * @method static Builder<static>|Fingerprint whereFirstSeenAt($value)
 * @method static Builder<static>|Fingerprint whereId($value)
 * @method static Builder<static>|Fingerprint whereIpAddress($value)
 * @method static Builder<static>|Fingerprint whereLastCheckedAt($value)
 * @method static Builder<static>|Fingerprint whereLastSeenAt($value)
 * @method static Builder<static>|Fingerprint whereRequestId($value)
 * @method static Builder<static>|Fingerprint whereSuspectScore($value)
 * @method static Builder<static>|Fingerprint whereUpdatedAt($value)
 * @method static Builder<static>|Fingerprint whereUserAgent($value)
 * @method static Builder<static>|Fingerprint whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Fingerprint extends Model
{
    use Blacklistable;
    use HasFactory;

    protected $attributes = [
        'suspect_score' => 0,
    ];

    protected $fillable = [
        'user_id',
        'fingerprint_id',
        'request_id',
        'ip_address',
        'user_agent',
        'suspect_score',
        'first_seen_at',
        'last_seen_at',
        'last_checked_at',
    ];

    protected $dispatchesEvents = [
        'created' => FingerprintCreated::class,
        'updated' => FingerprintUpdated::class,
    ];

    public static function trackFingerprint(
        ?int $userId,
        string $fingerprintId,
        ?string $requestId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        $fingerprint = static::where('fingerprint_id', $fingerprintId)
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->first();

        if ($fingerprint) {
            $fingerprint->update([
                'user_id' => $userId ?? $fingerprint->user_id,
                'request_id' => $requestId,
                'ip_address' => $ipAddress ?? $fingerprint->ip_address,
                'user_agent' => $userAgent ?? $fingerprint->user_agent,
                'last_seen_at' => now(),
            ]);

            return $fingerprint;
        }

        return static::create([
            'user_id' => $userId,
            'fingerprint_id' => $fingerprintId,
            'request_id' => $requestId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspect_score' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }
}
