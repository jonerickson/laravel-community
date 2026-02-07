<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Actions\Policies\RecordPolicyConsentAction;
use App\Enums\PolicyConsentContext;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\OnboardingService;
use App\Settings\RegistrationSettings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RegisterController
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly RegistrationSettings $registrationSettings,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(RegisterRequest $request): Response
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        event(new Registered($user));

        $policyIds = $this->registrationSettings->required_policy_ids;

        if (filled($policyIds)) {
            RecordPolicyConsentAction::execute(
                user: $user,
                policies: $policyIds,
                context: PolicyConsentContext::Onboarding,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                fingerprintId: $request->fingerprintId(),
            );
        }

        Auth::login($user);

        $this->onboardingService->advanceToStep(1);

        return inertia()->location(route('onboarding'));
    }
}
