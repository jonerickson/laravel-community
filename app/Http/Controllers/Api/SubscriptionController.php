<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SubscriptionCheckoutRequest;
use App\Http\Resources\ApiResource;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Auth;

class SubscriptionController
{
    public function __invoke(SubscriptionCheckoutRequest $request): ApiResource
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return ApiResource::error(
                message: 'Authentication required to subscribe.',
                errors: ['auth' => ['User must be authenticated.']],
                status: 401
            );
        }

        $validated = $request->validated();
        $productId = $validated['product_id'];
        $billingCycle = $validated['billing_cycle'] ?? 'monthly';

        /** @var Product $product */
        $product = Product::query()
            ->where('id', $productId)
            ->where('type', 'subscription')
            ->whereNotNull('external_product_id')
            ->first();

        if (! $product) {
            return ApiResource::error(
                message: 'Subscription product not found.',
                errors: ['product' => ['The selected product is not available for subscription.']],
                status: 404
            );
        }

        $price = $product->prices()
            ->where('is_active', true)
            ->where('interval', $billingCycle === 'yearly' ? 'year' : 'month')
            ->whereNotNull('external_price_id')
            ->first();

        if (! $price) {
            return ApiResource::error(
                message: "No $billingCycle pricing available for this subscription.",
                errors: ['price' => ["No $billingCycle price configured for this product."]],
                status: 400
            );
        }

        try {
            $checkout = $user->checkout([
                [
                    'price' => $price->external_price_id,
                    'quantity' => 1,
                ],
            ], [
                'success_url' => route('store.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('store.subscriptions'),
                'mode' => 'subscription',
                'billing_address_collection' => 'required',
                'customer_update' => [
                    'address' => 'auto',
                    'name' => 'auto',
                ],
                'metadata' => [
                    'product_id' => $product->id,
                    'price_id' => $price->id,
                    'billing_cycle' => $billingCycle,
                ],
            ]);

            return ApiResource::success(
                resource: [
                    'checkout_url' => $checkout->url,
                ],
                message: 'Subscription checkout session created successfully.',
                meta: [
                    'session_id' => $checkout->id,
                    'product_name' => $product->name,
                    'billing_cycle' => $billingCycle,
                ]
            );
        } catch (Exception $e) {
            return ApiResource::error('Failed to create subscription checkout: '.$e->getMessage(), [
                'checkout' => [$e->getMessage()],
            ]);
        }
    }
}
