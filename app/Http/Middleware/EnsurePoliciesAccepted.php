<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PolicyConsentContext;
use App\Http\Middleware\Concerns\BypassesForcedActionRoutes;
use App\Models\Policy;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class EnsurePoliciesAccepted
{
    use BypassesForcedActionRoutes;

    public function handle(Request $request, Closure $next): Response
    {
        if (($user = $request->user())
            && ! $this->isForcedActionRoute($request)
            && $this->hasOutstandingPolicies($user)
        ) {
            return $request->expectsJson()
                ? abort(403, 'You must accept updated policies to continue.')
                : ($request->inertia()
                    ? inertia()->location(URL::route('policies.accept.notice'))
                    : Redirect::guest(URL::route('policies.accept.notice'))
                );
        }

        return $next($request);
    }

    protected function hasOutstandingPolicies(User $user): bool
    {
        return Policy::query()
            ->active()
            ->effective()
            ->requiresAcceptance()
            ->whereDoesntHave('userConsents', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('context', PolicyConsentContext::Acceptance)
                    ->whereColumn('policy_consents.version', 'policies.version');
            })
            ->exists();
    }
}
