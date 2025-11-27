<?php

declare(strict_types=1);

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIntegration;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

class CallbackController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {
        //
    }

    public function __invoke(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $throwable) {
            Log::error('Integration login error', [
                'message' => $throwable->getMessage(),
            ]);

            return is_null($this->user)
                ? redirect()->intended(route('login'))->with('error', 'There was an error while trying to login. Please try again.')
                : redirect()->intended(route('settings.integrations.index'))
                    ->with([
                        'message' => 'There was an error while trying to connect your again. Please try again.',
                        'messageVariant' => 'error',
                    ]);
        }

        $integration = UserIntegration::with('user')->firstWhere([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
        ]);

        if (blank($integration) && is_null($this->user)) {
            return redirect()
                ->route('login')
                ->with('error', sprintf('No account found for this %s connection. Please create an account first or login using your username and password.', ucfirst($provider)));
        }

        if (blank($integration) && $this->user instanceof User) {
            $integration = $this->user->integrations()->create([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_name' => $socialUser->getName(),
                'provider_email' => $socialUser->getEmail(),
                'provider_avatar' => $socialUser->getAvatar(),
                'access_token' => property_exists($socialUser, 'token') ? $socialUser->token : null,
                'refresh_token' => property_exists($socialUser, 'refreshToken') ? $socialUser->refreshToken : null,
                'expires_at' => property_exists($socialUser, 'expiresIn') ? now()->addSeconds($socialUser->expiresIn) : null,
            ]);
        } else {
            $integration->update([
                'provider_name' => $socialUser->getName(),
                'provider_avatar' => $socialUser->getAvatar(),
                'access_token' => property_exists($integration, 'token') ? $integration->token : null,
                'refresh_token' => property_exists($socialUser, 'refreshToken') ? $socialUser->refreshToken : null,
                'expires_at' => property_exists($socialUser, 'expiresIn') ? now()->addSeconds($socialUser->expiresIn) : null,
            ]);
        }

        $integration->user->logIntegrationLogin($provider);

        $loggingIn = false;
        if (is_null($this->user)) {
            Auth::login($integration->user);

            $loggingIn = true;
        }

        return redirect()
            ->intended(route('dashboard'))
            ->with('message', $loggingIn
                ? 'You have been successfully logged in.'
                : 'You have been successfully connected your account.'
            );
    }
}
