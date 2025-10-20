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
        $request->except(['_token']);

        $this->user->forceFill([
            'onboarded_at' => now(),
        ]);

        // TODO: Finish custom fields
        //        foreach ($customData as $key => $value) {
        //            if (in_array($key, ['bio', 'role'])) {
        //                $this->user->forceFill([
        //                    $key => $value,
        //                ]);
        //            }
        //        }

        $this->onboardingService->advanceToStep(4);

        return inertia()->location(route('onboarding'));
    }
}
