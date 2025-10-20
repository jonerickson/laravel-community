<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\OnboardingCompleteRequest;
use App\Http\Requests\Onboarding\OnboardingUpdateRequest;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {}

    public function index(): Response|RedirectResponse
    {
        if ($this->user && filled($this->user->onboarded_at)) {
            return to_route('home')
                ->with('message', 'Your account has already been successfully onboarded.');
        }

        $customFields = [
            [
                'name' => 'bio',
                'label' => 'Tell us about yourself',
                'type' => 'textarea',
                'placeholder' => 'Share a bit about yourself...',
                'required' => false,
                'description' => 'This will appear on your profile',
            ],
            [
                'name' => 'role',
                'label' => 'What brings you here?',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['value' => 'developer', 'label' => 'Developer'],
                    ['value' => 'creator', 'label' => 'Content Creator'],
                    ['value' => 'player', 'label' => 'Player'],
                    ['value' => 'other', 'label' => 'Other'],
                ],
            ],
        ];

        $initialStep = $this->onboardingService->determineInitialStep($this->user);

        $hasDiscordIntegration = $this->user && $this->user->integrations()->where('provider', 'discord')->exists();
        $hasRobloxIntegration = $this->user && $this->user->integrations()->where('provider', 'roblox')->exists();

        return Inertia::render('onboarding/index', [
            'customFields' => $customFields,
            'initialStep' => $initialStep,
            'isAuthenticated' => (bool) $this->user,
            'completedSteps' => $this->onboardingService->getCompletedSteps(),
            'integrations' => [
                'discord' => [
                    'enabled' => config('services.discord.enabled', false),
                    'connected' => $hasDiscordIntegration,
                ],
                'roblox' => [
                    'enabled' => config('services.roblox.enabled', false),
                    'connected' => $hasRobloxIntegration,
                ],
            ],
            'emailVerified' => $this->user && $this->user->hasVerifiedEmail(),
        ]);
    }

    public function store(OnboardingCompleteRequest $request): RedirectResponse
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

        $this->user->save();

        $this->onboardingService->completeOnboarding();

        return to_route('home')
            ->with('message', 'Your onboarding has been successfully completed.');
    }

    public function update(OnboardingUpdateRequest $request): SymfonyResponse
    {
        $this->onboardingService->setCurrentStep($request->integer('step'));

        return inertia()->location(route('onboarding'));
    }
}
