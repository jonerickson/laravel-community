<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Data\FieldData;
use App\Data\PolicyData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\OnboardingUpdateRequest;
use App\Managers\PaymentManager;
use App\Models\Field;
use App\Models\Policy;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use App\Services\OnboardingService;
use App\Settings\RegistrationSettings;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        private readonly RegistrationSettings $registrationSettings,
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {}

    public function index(): Response|RedirectResponse
    {
        if ($this->user && filled($this->user->onboarded_at)) {
            return to_route('home')
                ->with('message', 'Your account has already been successfully onboarded.');
        }

        $customFields = Field::query()
            ->ordered()
            ->get()
            ->map(function (Field $field): FieldData {
                if (is_null($this->user)) {
                    return FieldData::from($field);
                }

                $userField = $this->user->fields->firstWhere('id', $field->id);
                $fieldData = FieldData::from($field);
                $fieldData->value = $userField?->pivot->value;

                return $fieldData;
            })
            ->toArray();

        $initialStep = $this->onboardingService->determineInitialStep($this->user);

        $integrations = optional($this->user, fn (User $user) => $user->integrations()->get()) ?? collect();

        $hasDiscordIntegration = $integrations->firstWhere('provider', 'discord');
        $hasRobloxIntegration = $integrations->firstWhere('provider', 'roblox');
        $hasSubscription = $this->user && $this->paymentManager->currentSubscription($this->user);

        $subscriptions = Product::query()
            ->subscriptions()
            ->visible()
            ->active()
            ->with(['prices' => fn (HasMany|Price $query) => $query->recurring()->active()->visible()])
            ->ordered()
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->values();

        $policies = Policy::query()->with('category')->whereIn('id', $this->registrationSettings->required_policy_ids)->get();

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
            'policies' => PolicyData::collect($policies),
        ]);
    }

    public function store(): RedirectResponse
    {
        $this->user->update([
            'onboarded_at' => now(),
        ]);

        $this->onboardingService->completeOnboarding();

        return redirect()
            ->intended(route('dashboard'))
            ->with('message', 'Your onboarding has been successfully completed.');
    }

    public function update(OnboardingUpdateRequest $request): SymfonyResponse
    {
        $this->onboardingService->setCurrentStep($request->integer('step'));

        return inertia()->location(route('onboarding'));
    }
}
