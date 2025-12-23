<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Commission;
use App\Models\Payout;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Eloquent
 * @mixin User
 */
trait CanBePaid
{
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'seller_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function currentBalance(): Attribute
    {
        return Attribute::make(
            get: fn (?int $value): float => filled($value) ? (float) $value / 100 : 0.00,
            set: fn (float $value): int => (int) ($value * 100),
        );
    }

    public function externalPayoutAccountOnboardingComplete(): Attribute
    {
        return Attribute::get(fn (): bool => ! is_null($this->external_payout_account_onboarded_at));
    }

    public function hasPayoutAccount(): bool
    {
        return filled($this->external_payout_account_id);
    }

    public function payoutAccountId(): ?string
    {
        return $this->external_payout_account_id;
    }

    public function isPayoutAccountOnboardingComplete(): bool
    {
        return $this->external_payout_account_onboarding_complete === true;
    }
}
