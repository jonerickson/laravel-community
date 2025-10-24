<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class ForceOnboarding
{
    public function handle(Request $request, Closure $next): Response
    {
        if (request()->user() && ! $request->user()->onboarded_at) {
            Redirect::setIntendedUrl($request->path());

            return to_route('onboarding');
        }

        return $next($request);
    }
}
