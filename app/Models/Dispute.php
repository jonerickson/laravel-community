<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
