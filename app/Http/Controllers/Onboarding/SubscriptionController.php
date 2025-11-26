<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Requests\Onboarding\OnboardingSubscribeRequest;
use App\Managers\PaymentManager;
use App\Models\User;
use App\Services\ShoppingCartService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentManager $paymentManager,
        private readonly ShoppingCartService $shoppingCartService,
        #[CurrentUser]
        private readonly User $user,
    ) {}

    public function __invoke(OnboardingSubscribeRequest $request): Response
    {
        $price = $request->getPrice();

        $this->authorize('view', $price->product);

        $checkoutUrl = $this->paymentManager->startSubscription(
            order: $request->generateOrder($this->user),
            successUrl: route('onboarding'),
            cancelUrl: route('onboarding'),
        );

        if (! $checkoutUrl) {
            return back()->with([
                'message' => 'We were unable to start your subscription. Please try again later.',
                'messageVariant' => 'error',
            ]);
        }

        return inertia()->location($checkoutUrl);
    }
}
