<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\BypassesForcedActionRoutes;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as BaseEnsureEmailIsVerified;
use Override;

class EnsureEmailIsVerified extends BaseEnsureEmailIsVerified
{
    use BypassesForcedActionRoutes;

    #[Override]
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if ($this->isForcedActionRoute($request)) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
