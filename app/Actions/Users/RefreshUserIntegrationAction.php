<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Actions\Action;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Throwable;

class RefreshUserIntegrationAction extends Action
{
    public function __construct(
        protected UserIntegration $integration,
    ) {
        //
    }

    public function __invoke(): bool
    {
        if (blank($this->integration->refresh_token) || (filled($this->integration->expires_at) && $this->integration->expires_at->isPast())) {
            return false;
        }

        try {
            /** @var AbstractProvider $provider */
            $provider = Socialite::driver($this->integration->provider);

            $token = $provider->refreshToken($this->integration->refresh_token);
            $user = $provider->userFromToken($token->token);

            $this->integration->update([
                'provider_name' => $user->getName(),
                'provider_avatar' => $user->getAvatar(),
                'last_synced_at' => now(),
                'access_token' => $token->token,
                'refresh_token' => $token->refreshToken,
                'expires_at' => now()->addSeconds($token->expiresIn),
            ]);
        } catch (Throwable $throwable) {
            Log::error('Discord refresh action failed', [
                'message' => $throwable->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
