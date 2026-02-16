<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\BypassesForcedActionRoutes;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class ForceOnboarding
{
    use BypassesForcedActionRoutes;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->onboarded_at && ! $this->isForcedActionRoute($request)) {
            Redirect::setIntendedUrl($request->path());

            return to_route('onboarding');
        }

        return $next($request);
    }
}
