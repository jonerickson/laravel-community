<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $seller_id
 * @property float $amount
 * @property PayoutStatus $status
 * @property string|null $payout_method
 * @property string|null $external_payout_id
 * @property string|null $notes
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property int|null $processed_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Commission> $commissions
 * @property-read int|null $commissions_count
 * @property-read User|null $processor
 * @property-read User $seller
 *
 * @method static Builder<static>|Payout completed()
 * @method static \Database\Factories\PayoutFactory factory($count = null, $state = [])
 * @method static Builder<static>|Payout newModelQuery()
 * @method static Builder<static>|Payout newQuery()
 * @method static Builder<static>|Payout pending()
 * @method static Builder<static>|Payout query()
 * @method static Builder<static>|Payout whereAmount($value)
 * @method static Builder<static>|Payout whereCreatedAt($value)
 * @method static Builder<static>|Payout whereExternalPayoutId($value)
 * @method static Builder<static>|Payout whereFailureReason($value)
 * @method static Builder<static>|Payout whereId($value)
 * @method static Builder<static>|Payout whereNotes($value)
 * @method static Builder<static>|Payout wherePayoutMethod($value)
 * @method static Builder<static>|Payout whereProcessedAt($value)
 * @method static Builder<static>|Payout whereProcessedBy($value)
 * @method static Builder<static>|Payout whereSellerId($value)
 * @method static Builder<static>|Payout whereStatus($value)
 * @method static Builder<static>|Payout whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'amount',
        'status',
        'payout_method',
        'external_payout_id',
        'failure_reason',
        'notes',
        'processed_at',
        'processed_by',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): float => (float) $value / 100,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    public function canRetry(): bool
    {
        return $this->status === PayoutStatus::Failed;
    }

    public function canCancel(): bool
    {
        return $this->status === PayoutStatus::Pending;
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', PayoutStatus::Pending);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', PayoutStatus::Completed);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'status' => PayoutStatus::class,
            'processed_at' => 'datetime',
        ];
    }
}
