<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SubscriptionCheckoutRequest;
use App\Http\Resources\ApiResource;
use App\Managers\PaymentManager;
use App\Models\ProductPrice;
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

        if (! $price = ProductPrice::find($validated['price_id'])) {
            return ApiResource::error(
                message: 'Unable to find product price.',
                errors: ['price_id' => ['Unable to find product price.']],
                status: 400
            );
        }

        $result = $this->paymentManager->startSubscription(
            user: $user,
            price: $price,
            returnUrl: route('store.subscriptions')
        );

        if (! $result) {
            return ApiResource::error(
                message: 'Unable to start subscription.',
                errors: ['price_id' => ['Unable to start subscription. Please try again later.']],
                status: 400
            );
        }

        return ApiResource::success(
            resource: $result,
            message: 'Subscription started successfully.',
        );
    }
}
