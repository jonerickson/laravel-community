<?php

declare(strict_types=1);

namespace App\Pipes\Stripe;

use App\Data\CustomerData;
use App\Managers\PaymentManager;
use App\Models\Order;
use Closure;
use Exception;

class EnsureCustomerExists
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
        if (! $this->paymentManager->getCustomer($order->user) instanceof CustomerData) {
            $result = $this->paymentManager->createCustomer($order->user, true);

            if (! $result) {
                throw new Exception('Failed to create Stripe customer.');
            }
        }

        return $next($order);
    }
}
