<?php

declare(strict_types=1);

namespace App\Pipes\Stripe;

use App\Data\PriceData;
use App\Managers\PaymentManager;
use App\Models\Order;
use Closure;
use Exception;

class EnsurePricesExist
{
    public function __construct(
        private readonly PaymentManager $paymentManager,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(Order $order, Closure $next): mixed
    {
        $order->load(['items.price']);

        foreach ($order->items as $item) {
            if (! $item->price) {
                continue;
            }

            $price = $item->price;

            if (! $price->external_price_id) {
                $priceData = $this->paymentManager->createPrice($price);

                if (! $priceData instanceof PriceData) {
                    throw new Exception('Failed to create price in payment processor for price ID '.$price->id);
                }
            }
        }

        return $next($order);
    }
}
