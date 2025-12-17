<?php

declare(strict_types=1);

namespace App\Pipes\Stripe;

use App\Data\PaymentMethodData;
use App\Managers\PaymentManager;
use App\Models\Order;
use Closure;
use Exception;

class EnsureDefaultPaymentMethod
{
    public function __construct(
        private readonly PaymentManager $paymentManager,
    ) {
        //
    }

    /**
     * @throws Exception
     */
    public function __invoke(Order $order, Closure $next)
    {
        $defaultPaymentMethod = $order->user->defaultPaymentMethod();

        if (! $defaultPaymentMethod) {
            $paymentMethods = $this->paymentManager->listPaymentMethods($order->user);

            if (! is_null($paymentMethods) && $paymentMethods->isNotEmpty()) {
                /** @var PaymentMethodData $paymentMethod */
                $paymentMethod = $paymentMethods->first();

                $order->user->updateDefaultPaymentMethod($paymentMethod->id);
            }
        }

        $order->user->updateDefaultPaymentMethodFromStripe();

        return $next($order);
    }
}
