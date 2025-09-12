<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SubscriptionCheckoutRequest;
use App\Http\Resources\ApiResource;
use App\Managers\PaymentManager;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class SubscriptionController
{
    public function __construct(protected PaymentManager $paymentManager)
    {
        //
    }

    public function __invoke(SubscriptionCheckoutRequest $request)
    {
        $user = Auth::user();

        if (! $user) {
            return ApiResource::error(
                message: 'Authentication required to subscribe.',
                errors: ['auth' => ['User must be authenticated.']],
                status: 401
            );
        }

        $validated = $request->validated();
        $productId = $validated['product_id'];

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

        return $this->paymentManager->startSubscription(
            user: $user,
            product: $product,
            price: $validated['price'],
            returnUrl: route('store.subscriptions')
        );
    }
}
