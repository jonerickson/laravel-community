<?php

declare(strict_types=1);

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectController extends Controller
{
    public function __invoke(Request $request, string $provider): RedirectResponse
    {
        if ($request->has('redirect') && $request->filled('redirect')) {
            $request->session()->put('url.intended', urldecode($request->query('redirect')));
        }

        return Socialite::driver($provider)->redirect();
    }
}
