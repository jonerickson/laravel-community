<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutDriver;
use App\Enums\PayoutStatus;
use App\Traits\HasAuthor;
use App\Traits\HasReferenceId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $reference_id
 * @property int $seller_id
 * @property float $amount
 * @property PayoutStatus $status
 * @property PayoutDriver|null $payout_method
 * @property string|null $external_payout_id
 * @property string|null $notes
 * @property string|null $failure_reason
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $author
 * @property-read mixed $author_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Commission> $commissions
 * @property-read int|null $commissions_count
 * @property-read User|null $creator
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
 * @method static Builder<static>|Payout whereCreatedBy($value)
 * @method static Builder<static>|Payout whereExternalPayoutId($value)
 * @method static Builder<static>|Payout whereFailureReason($value)
 * @method static Builder<static>|Payout whereId($value)
 * @method static Builder<static>|Payout whereNotes($value)
 * @method static Builder<static>|Payout wherePayoutMethod($value)
 * @method static Builder<static>|Payout whereReferenceId($value)
 * @method static Builder<static>|Payout whereSellerId($value)
 * @method static Builder<static>|Payout whereStatus($value)
 * @method static Builder<static>|Payout whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Payout extends Model
{
    use HasAuthor;
    use HasFactory;
    use HasReferenceId;

    protected $attributes = [
        'status' => PayoutStatus::Pending,
    ];

    protected $fillable = [
        'seller_id',
        'amount',
        'status',
        'payout_method',
        'external_payout_id',
        'failure_reason',
        'notes',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
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
            'payout_method' => PayoutDriver::class,
            'status' => PayoutStatus::class,
            'processed_at' => 'datetime',
        ];
    }
}
