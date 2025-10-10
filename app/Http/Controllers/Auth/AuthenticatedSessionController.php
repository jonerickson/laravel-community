<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        #[Config('services.discord.enabled')]
        protected bool $discordEnabled,
        #[Config('services.roblox.enabled')]
        protected bool $robloxEnabled
    ) {
        //
    }

    public function create(Request $request): Response
    {
        if ($request->has('redirect') && $request->filled('redirect')) {
            $request->session()->put('url.intended', urldecode($request->query('redirect')));
        }

        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'discordEnabled' => $this->discordEnabled,
            'robloxEnabled' => $this->robloxEnabled,
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
