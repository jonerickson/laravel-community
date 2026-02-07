<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PolicyConsentContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
