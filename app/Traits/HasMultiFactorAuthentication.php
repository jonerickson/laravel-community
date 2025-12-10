<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;

/**
 * @mixin User
 */
trait HasMultiFactorAuthentication
{
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! is_null($this->getAppAuthenticationSecret())
            && ! is_null($this->app_authentication_confirmed_at);
    }

    public function disableTwoFactorAuthentication(): void
    {
        $this->app_authentication_secret = null;
        $this->save();
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }

    protected function initializeHasMultiFactorAuthentication(): void
    {
        $this->mergeHidden([
            'app_authentication_secret',
            'app_authentication_recovery_codes',
            'app_authentication_confirmed_at',
        ]);

        $this->mergeCasts([
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'app_authentication_confirmed_at' => 'datetime',
        ]);

        $this->mergeFillable([
            'app_authentication_secret',
            'app_authentication_recovery_codes',
            'app_authentication_confirmed_at',
        ]);
    }
}
