<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApiResource;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Product;
use App\Services\ShoppingCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController
{
    public function __construct(
        private readonly ShoppingCartService $cartService,
        private readonly PaymentManager $paymentManager,
    ) {}

    public function __invoke(Request $request): ApiResource
    {
        $user = Auth::user();

        if (! $user) {
            return ApiResource::error(
                message: 'Authentication required to checkout.',
                errors: ['auth' => ['User must be authenticated.']],
                status: 401
            );
        }

        $cartItems = $this->cartService->getCartItems();

        if (blank($cartItems)) {
            return ApiResource::error(
                message: 'Cart is empty.',
                errors: ['cart' => ['Cart cannot be empty.']],
                status: 400
            );
        }

        $productPrices = [];
        foreach ($cartItems as $item) {
            /** @var Product $product */
            $product = $item['product'];

            if (! $product || ! $product->external_product_id) {
                return ApiResource::error(
                    message: "{$item['name']} is not available for purchase.",
                    errors: ['product' => ["{$item['name']} is not configured for purchase."]],
                    status: 400
                );
            }

            $selectedPrice = null;
            if ($item['price_id']) {
                $selectedPrice = $product->prices()->where('id', $item['price_id'])->first();
            }

            if (! $selectedPrice) {
                $selectedPrice = $product->defaultPrice;
            }

            if (! $selectedPrice || ! $selectedPrice->external_price_id) {
                return ApiResource::error(
                    message: "No prices are configured for {$item['name']}.",
                    errors: ['price' => ["Price not configured for {$item['name']}."]],
                    status: 400
                );
            }

            $productPrices[] = $selectedPrice;
        }

        if (empty($productPrices)) {
            return ApiResource::error(
                message: 'Cart is empty.',
                errors: ['cart' => ['Cart cannot be empty.']],
                status: 400
            );
        }

        $order = Order::create([
            'user_id' => $user->id,
        ]);

        foreach ($productPrices as $price) {
            $order->items()->create([
                'product_id' => $price->product_id,
                'price_id' => $price->id,
            ]);
        }

        $result = $this->paymentManager->redirectToCheckout(
            user: $user,
            order: $order,
            prices: $productPrices
        );

        if (! $result) {
            return ApiResource::error(
                message: 'Failed to create checkout session'
            );
        }

        return ApiResource::success(
            resource: [
                'checkout_url' => $result,
            ],
            message: 'Checkout session created successfully.',
        );
    }
}
