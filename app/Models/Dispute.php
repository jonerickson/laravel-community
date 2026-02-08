<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $order_id
 * @property string $external_dispute_id
 * @property string $external_charge_id
 * @property string|null $external_payment_intent_id
 * @property DisputeStatus $status
 * @property DisputeReason|null $reason
 * @property int $amount
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $evidence_due_by
 * @property bool $is_charge_refundable
 * @property string|null $network_reason_code
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Order $order
 * @property-read User $user
 *
 * @method static \Database\Factories\DisputeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereEvidenceDueBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereExternalChargeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereExternalDisputeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereExternalPaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereIsChargeRefundable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereNetworkReasonCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Dispute whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Dispute extends Model
{
    /** @use HasFactory<\Database\Factories\DisputeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'external_dispute_id',
        'external_charge_id',
        'external_payment_intent_id',
        'status',
        'reason',
        'amount',
        'currency',
        'evidence_due_by',
        'is_charge_refundable',
        'network_reason_code',
        'metadata',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DisputeStatus::class,
            'reason' => DisputeReason::class,
            'amount' => 'integer',
            'is_charge_refundable' => 'boolean',
            'evidence_due_by' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
