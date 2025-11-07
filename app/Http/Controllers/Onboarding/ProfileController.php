<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Requests\Onboarding\OnboardingProfileRequest;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Container\Attributes\CurrentUser;
use Symfony\Component\HttpFoundation\Response;

class ProfileController
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
        private readonly OnboardingService $onboardingService,
    ) {}

    public function __invoke(OnboardingProfileRequest $request): Response
    {
        $this->user->forceFill([
            'onboarded_at' => now(),
        ]);

        if ($request->has('fields')) {
            foreach ($request->validated('fields', []) as $fieldId => $value) {
                $this->user->fields()->syncWithoutDetaching([
                    (int) $fieldId => ['value' => $value],
                ]);
            }
        }

        $this->onboardingService->advanceToStep(4);

        return inertia()->location(route('onboarding'));
    }
}
