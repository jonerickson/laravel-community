<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Requests\Onboarding\OnboardingSubscribeRequest;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Services\ShoppingCartService;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController
{
    public function __construct(
        private readonly PaymentManager $paymentManager,
        private readonly ShoppingCartService $shoppingCartService,
    ) {}

    public function __invoke(OnboardingSubscribeRequest $request): Response
    {
        $order = $this->shoppingCartService->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return back()
                ->with('message', 'We were unable to start your subscription. Please try again later.')
                ->with('messageVariant', 'error');
        }

        $this->shoppingCartService->addItem(
            priceId: 1,
            quantity: 1
        );

        $checkoutUrl = $this->paymentManager->startSubscription(
            order: $this->shoppingCartService->getOrCreatePendingOrder(),
            successUrl: route('onboarding')
        );

        if (! $checkoutUrl) {
            return back()
                ->with('message', 'We were unable to start your subscription. Please try again later.')
                ->with('messageVariant', 'error');
        }

        return inertia()->location($checkoutUrl);
    }
}
