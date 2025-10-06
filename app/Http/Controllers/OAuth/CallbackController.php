<?php

declare(strict_types=1);

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CallbackController extends Controller
{
    public function __invoke(string $provider): RedirectResponse
    {
        $socialUser = Socialite::driver($provider)->user();

        if (Auth::user() && Auth::user()->email !== $socialUser->getEmail()) {
            throw ValidationException::withMessages([
                'email' => 'The email connected to your social account does not match the email that is currently logged in. Please connect an account that uses the same email.',
            ]);
        }

        $integration = UserIntegration::firstOrNew([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
        ], [
            'provider_name' => $socialUser->getName(),
            'provider_email' => $socialUser->getEmail(),
            'provider_avatar' => $socialUser->getAvatar(),
        ]);

        if (blank($integration->getKey())) {
            $user = User::firstOrCreate([
                'email' => $email = $socialUser->getEmail(),
            ], [
                'name' => $socialUser->getName(),
                'email_verified_at' => $email ? now() : null,
            ]);

            $integration->user()->associate($user);
            $integration->save();
        } else {
            $user = $integration->user;
        }

        $user->logSocialLogin($provider);

        if (! Auth::check()) {
            Auth::login($user);
        }

        return redirect()
            ->intended(route('dashboard', absolute: false))
            ->with('message', 'You have been successfully logged in.');
    }
}
