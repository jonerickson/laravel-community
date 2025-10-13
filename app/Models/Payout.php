<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'payout_method',
        'external_payout_id',
        'notes',
        'processed_at',
        'processed_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function amount(): Attribute
    {
        return Attribute::make(
            get: fn (int $value): float => $value / 100,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    protected function casts(): array
    {
        return [
            'status' => PayoutStatus::class,
            'processed_at' => 'datetime',
        ];
    }
}
