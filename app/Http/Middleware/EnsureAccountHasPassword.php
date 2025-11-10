<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountHasPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if (($user = $request->user()) && blank($user->password) && ! $request->routeIs('set-password.notice')) {
            return $request->expectsJson()
                ? abort(403, 'Your account must have a password set.')
                : ($request->inertia()
                    ? inertia()->location(URL::route('set-password.notice'))
                    : Redirect::guest(URL::route('set-password.notice'))
                );
        }

        return $next($request);
    }
}
