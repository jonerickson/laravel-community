<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        if (request()->user() && ! $request->user()->onboarded_at) {
            return to_route('onboarding');
        }

        return $next($request);
    }
}
