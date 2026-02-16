<?php

declare(strict_types=1);

namespace App\Http\Middleware\Concerns;

use Illuminate\Http\Request;

trait BypassesForcedActionRoutes
{
    protected function isForcedActionRoute(Request $request): bool
    {
        return $request->routeIs(
            'set-email.notice',
            'set-email.verify',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'set-password.notice',
            'set-password.verify',
            'onboarding',
            'onboarding.*',
            'policies.accept.notice',
            'policies.accept.store',
            'login',
            'logout',
        );
    }
}
