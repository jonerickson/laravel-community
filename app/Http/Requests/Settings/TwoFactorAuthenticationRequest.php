<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorAuthenticationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function ensureStateIsValid(): void
    {
        $currentTime = time();

        if (! $this->user()->hasEnabledTwoFactorAuthentication()) {
            $this->session()->put('two_factor_empty_at', $currentTime);
        }

        if ($this->hasJustBegunConfirmingTwoFactorAuthentication()) {
            $this->session()->put('two_factor_confirming_at', $currentTime);
        }

        if ($this->neverFinishedConfirmingTwoFactorAuthentication($currentTime)) {
            $this->user()->disableTwoFactorAuthentication();

            $this->session()->put('two_factor_empty_at', $currentTime);
            $this->session()->remove('two_factor_confirming_at');
        }
    }

    protected function hasJustBegunConfirmingTwoFactorAuthentication(): bool
    {
        return ! is_null($this->user()->app_authentication_secret) &&
            is_null($this->user()->app_authentication_confirmed_at) &&
            $this->session()->has('two_factor_empty_at') &&
            is_null($this->session()->get('two_factor_confirming_at'));
    }

    protected function neverFinishedConfirmingTwoFactorAuthentication(int $currentTime): bool
    {
        return ! $this->session()->hasOldInput('code') &&
            is_null($this->user()->app_authentication_confirmed_at) &&
            $this->session()->get('two_factor_confirming_at', 0) !== $currentTime;
    }
}
