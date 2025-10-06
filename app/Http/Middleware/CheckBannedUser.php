<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\BannedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckBannedUser
{
    /**
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (($user = request()->user())) {
            throw_if($user->is_banned && ! $request->routeIs('policies.*') && ! $request->routeIs('api.fingerprint'), new BannedException(
                fingerprint: $user->fingerprints->first(),
            ));
        }

        return $next($request);
    }
}
