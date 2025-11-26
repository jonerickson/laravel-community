<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        #[Config('services.discord.enabled')]
        protected bool $discordEnabled,
    ) {
        //
    }

    public function create(Request $request): Response
    {
        if ($request->has('redirect') && $request->filled('redirect')) {
            Redirect::setIntendedUrl(urldecode($request->query('redirect')));
        }

        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
            'discordEnabled' => $this->discordEnabled,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(LoginRequest $request): SymfonyResponse
    {
        $intended = Uri::of(Redirect::getIntendedUrl() ?? '');

        $request->authenticate();

        $request->session()->regenerate();

        if (! $intended->isEmpty() && Str::of($intended->path())->match('/^(admin|marketplace)\//')) {
            return inertia()->location($intended->value());
        }

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
