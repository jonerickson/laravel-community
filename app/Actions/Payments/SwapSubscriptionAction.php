<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Action;
use App\Data\SubscriptionData;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Managers\PaymentManager;
use App\Models\Price;
use App\Models\User;
use Throwable;

class SwapSubscriptionAction extends Action
{
    public function __construct(
        protected User $user,
        protected Price $price,
        protected ProrationBehavior $prorationBehavior,
        protected PaymentBehavior $paymentBehavior,
    ) {
        //
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): bool|SubscriptionData
    {
        if (! $this->price->external_price_id) {
            return false;
        }

        $paymentManager = app(PaymentManager::class);

        if (! $paymentManager->currentSubscription($this->user)) {
            return false;
        }

        return $paymentManager->swapSubscription(
            user: $this->user,
            price: $this->price,
            prorationBehavior: $this->prorationBehavior,
            paymentBehavior: $this->paymentBehavior,
        );
    }
}
