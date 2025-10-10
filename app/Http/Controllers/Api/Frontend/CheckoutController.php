<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\CheckoutData;
use App\Http\Resources\ApiResource;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ShoppingCartService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;

class CheckoutController
{
    public function __construct(
        #[CurrentUser]
        private readonly ?User $user,
        private readonly ShoppingCartService $cartService,
        private readonly PaymentManager $paymentManager,
    ) {}

    public function __invoke(Request $request): ApiResource
    {
        if (! $this->user instanceof User) {
            return ApiResource::error(
                message: 'Authentication is required to checkout.',
                errors: ['auth' => ['User must be authenticated.']],
                status: 401
            );
        }

        $cartItems = $this->cartService->getCartItems();

        if (blank($cartItems)) {
            return ApiResource::error(
                message: 'Your cart is currently empty.',
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

        if ($productPrices === []) {
            return ApiResource::error(
                message: 'Your cart is currently empty.',
                errors: ['cart' => ['Cart cannot be empty.']],
                status: 400
            );
        }

        $order = Order::create([
            'user_id' => $this->user->id,
        ]);

        foreach ($productPrices as $price) {
            $order->items()->create([
                'product_id' => $price->product_id,
                'price_id' => $price->id,
            ]);
        }

        $result = $this->paymentManager->getCheckoutUrl(
            order: $order,
        );

        if ($result === false || ($result === '' || $result === '0')) {
            return ApiResource::error(
                message: 'Failed to create checkout session. Please try again.',
            );
        }

        $checkoutData = CheckoutData::from([
            'checkoutUrl' => $result,
        ]);

        return ApiResource::success(
            resource: $checkoutData,
        );
    }
}
