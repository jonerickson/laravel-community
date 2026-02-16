<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Policies\RecordPolicyConsentAction;
use App\Data\PolicyData;
use App\Enums\PolicyConsentContext;
use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AcceptPoliciesController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $outstandingPolicies = $this->getOutstandingPolicies($request);

        if ($outstandingPolicies->isEmpty()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('auth/accept-policies', [
            'policies' => PolicyData::collect($outstandingPolicies),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(Request $request): RedirectResponse
    {
        $outstandingPolicies = $this->getOutstandingPolicies($request);

        if ($outstandingPolicies->isNotEmpty()) {
            RecordPolicyConsentAction::execute(
                user: $request->user(),
                policies: $outstandingPolicies,
                context: PolicyConsentContext::Acceptance,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                fingerprintId: $request->fingerprintId(),
            );
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * @return Collection<int, Policy>
     */
    private function getOutstandingPolicies(Request $request): Collection
    {
        return Policy::query()
            ->active()
            ->effective()
            ->requiresAcceptance()
            ->whereDoesntHave('userConsents', function (Builder $query) use ($request): void {
                $query->where('user_id', $request->user()->id)
                    ->where('context', PolicyConsentContext::Acceptance)
                    ->whereColumn('policy_consents.version', 'policies.version');
            })
            ->with('category')
            ->get();
    }
}
