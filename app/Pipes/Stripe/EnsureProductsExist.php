<?php

declare(strict_types=1);

namespace App\Pipes\Stripe;

use App\Data\ProductData;
use App\Managers\PaymentManager;
use App\Models\Order;
use Closure;
use Exception;

class EnsureProductsExist
{
    public function __construct(
        private readonly PaymentManager $paymentManager,
    ) {}

    /**
     * @throws Exception
     */
    public function __invoke(Order $order, Closure $next): mixed
    {
        $order->load(['items.price.product']);

        foreach ($order->items as $item) {
            if (! $item->price) {
                continue;
            }

            $product = $item->price->product;

            if (! $product->external_product_id) {
                $productData = $this->paymentManager->createProduct($product);

                if (! $productData instanceof ProductData) {
                    throw new Exception('Failed to create product in payment processor for product ID '.$product->id);
                }
            }
        }

        return $next($order);
    }
}
