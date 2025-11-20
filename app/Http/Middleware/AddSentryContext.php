<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

use function Sentry\configureScope;

class AddSentryContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && app()->bound('sentry')) {
            configureScope(function (Scope $scope): void {
                $scope->setUser([
                    'id' => Auth::user()->getAuthIdentifier(),
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ]);
            });
        }

        return $next($request);
    }
}
