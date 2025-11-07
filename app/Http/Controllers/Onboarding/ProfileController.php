<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Requests\Onboarding\OnboardingProfileRequest;
use App\Models\Field;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Collection;
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

        $sync = Collection::make($request->validated())->mapWithKeys(function ($value, $key): array {
            $field = Field::where('name', $key)->first();

            return [$field->id => [
                'value' => $value,
            ]];
        })->toArray();

        $this->user->fields()->sync($sync);

        $this->onboardingService->advanceToStep(4);

        return inertia()->location(route('onboarding'));
    }
}
