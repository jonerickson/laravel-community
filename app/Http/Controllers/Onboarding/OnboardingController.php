<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\OnboardingUpdateRequest;
use App\Managers\PaymentManager;
use App\Models\Product;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly PaymentManager $paymentManager,
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
        $hasSubscription = $this->user && $this->paymentManager->currentSubscription($this->user);

        $subscriptions = Product::query()
            ->subscriptions()
            ->visible()
            ->with('prices')
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->values();

        return Inertia::render('onboarding/index', [
            'customFields' => $customFields,
            'initialStep' => $initialStep,
            'isAuthenticated' => (bool) $this->user,
            'completedSteps' => $this->onboardingService->getCompletedSteps(),
            'subscriptions' => ProductData::collect($subscriptions),
            'hasSubscription' => $hasSubscription,
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

    public function store(): RedirectResponse
    {
        $this->user->update([
            'onboarded_at' => now(),
        ]);

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
