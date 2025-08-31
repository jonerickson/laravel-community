<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApiResource;
use App\Services\ShoppingCartService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController
{
    public function __construct(
        private readonly ShoppingCartService $cartService
    ) {}

    public function __invoke(Request $request): ApiResource
    {
        $user = Auth::guard('api')->user();
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

        $lineItems = [];
        foreach ($cartItems as $item) {
            $product = $item['product'];
            if (! $product || ! $product->stripe_product_id) {
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

            if (! $selectedPrice || ! $selectedPrice->stripe_price_id) {
                return ApiResource::error(
                    message: "Price not configured for {$item['name']}.",
                    errors: ['price' => ["Price not configured for {$item['name']}."]],
                    status: 400
                );
            }

            $lineItems[] = [
                'price' => $selectedPrice->stripe_price_id,
                'quantity' => $item['quantity'],
            ];
        }

        try {
            $checkout = $user->checkout($lineItems, [
                'success_url' => route('store.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('store.cart.index'),
                'mode' => 'subscription',
                'metadata' => [
                    'cart_items' => json_encode(array_map(fn (array $item): array => [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ], $cartItems)),
                ],
            ]);

            return ApiResource::success(
                resource: [
                    'checkout_url' => $checkout->url,
                ],
                message: 'Checkout session created successfully.',
                meta: [
                    'session_id' => $checkout->id,
                ]
            );
        } catch (Exception $e) {
            return ApiResource::error('Failed to create checkout session: '.$e->getMessage(), [
                'checkout' => [$e->getMessage()],
            ]);
        }
    }
}
