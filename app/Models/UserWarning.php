<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasAuthor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $warning_id
 * @property int|null $warning_consequence_id
 * @property int|null $created_by
 * @property string|null $reason
 * @property int $points_at_issue
 * @property \Illuminate\Support\Carbon $points_expire_at
 * @property \Illuminate\Support\Carbon|null $consequence_expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read User|null $creator
 * @property-read User $user
 * @property-read Warning $warning
 * @property-read WarningConsequence|null $warningConsequence
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning activeConsequence()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning expired()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereConsequenceExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning wherePointsAtIssue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning wherePointsExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereWarningConsequenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserWarning whereWarningId($value)
 *
 * @mixin \Eloquent
 */
class UserWarning extends Model
{
    use HasAuthor;

    protected $table = 'users_warnings';

    protected $fillable = [
        'user_id',
        'warning_id',
        'warning_consequence_id',
        'reason',
        'points_at_issue',
        'points_expire_at',
        'consequence_expires_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warning(): BelongsTo
    {
        return $this->belongsTo(Warning::class);
    }

    public function warningConsequence(): BelongsTo
    {
        return $this->belongsTo(WarningConsequence::class);
    }

    public function isActive(): bool
    {
        return $this->points_expire_at->isFuture();
    }

    public function hasActiveConsequence(): bool
    {
        return $this->consequence_expires_at?->isFuture() ?? false;
    }

    public function scopeActive($query)
    {
        return $query->where('points_expire_at', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('points_expire_at', '<=', Carbon::now());
    }

    public function scopeActiveConsequence($query)
    {
        return $query->where('consequence_expires_at', '>', Carbon::now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_at_issue' => 'integer',
            'points_expire_at' => 'datetime',
            'consequence_expires_at' => 'datetime',
        ];
    }
}
