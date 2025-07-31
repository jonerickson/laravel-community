<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApiResource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CheckoutController
{
    public function __invoke(Request $request): JsonResource
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return ApiResource::error('Authentication required to checkout', ['auth' => ['User must be authenticated.']], 401);
        }

        $cartItems = $this->getCartItems();
        if (empty($cartItems)) {
            return ApiResource::error('Cart is empty', ['cart' => ['Cart cannot be empty.']], 400);
        }

        $lineItems = [];
        foreach ($cartItems as $item) {
            $product = $item['product'];
            if (! $product || ! $product->stripe_product_id) {
                return ApiResource::error("{$item['name']} is not available for purchase.", [
                    'product' => ["{$item['name']} is not configured for purchase."],
                ], 400);
            }

            $selectedPrice = null;
            if ($item['price_id']) {
                $selectedPrice = $product->prices()->where('id', $item['price_id'])->first();
            }

            if (! $selectedPrice) {
                $selectedPrice = $product->defaultPrice;
            }

            if (! $selectedPrice || ! $selectedPrice->stripe_price_id) {
                return ApiResource::error("Price not configured for {$item['name']}", [
                    'price' => ["Price not configured for {$item['name']}."],
                ], 400);
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
                'metadata' => [
                    'cart_items' => json_encode(array_map(fn ($item) => [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ], $cartItems)),
                ],
            ]);

            return ApiResource::success([
                'checkout_url' => $checkout->url,
            ], 'Checkout session created successfully', [
                'session_id' => $checkout->id,
            ]);
        } catch (Exception $e) {
            return ApiResource::error('Failed to create checkout session: '.$e->getMessage(), [
                'checkout' => [$e->getMessage()],
            ]);
        }
    }
}
