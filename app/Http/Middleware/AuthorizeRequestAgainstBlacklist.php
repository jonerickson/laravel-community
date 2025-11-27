<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\BlacklistedException;
use App\Services\BlacklistService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthorizeRequestAgainstBlacklist
{
    public function __construct(
        protected readonly BlacklistService $blacklistService
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shouldSkipChecks = $request->routeIs('policies.*')
            || $request->routeIs('login')
            || $request->routeIs('livewire.*')
            || $request->routeIs('filament.*')
            || $request->routeIs('admin.*')
            || $request->routeIs('api.fingerprint');

        if (! $shouldSkipChecks && ($blacklist = $this->blacklistService->isBlacklisted())) {
            throw new BlacklistedException(
                blacklist: $blacklist,
            );
        }

        return $next($request);
    }
}
