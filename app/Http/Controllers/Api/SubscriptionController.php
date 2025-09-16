<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\SubscriptionCheckoutRequest;
use App\Http\Resources\ApiResource;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Price;
use Illuminate\Support\Facades\Auth;

class SubscriptionController
{
    public function __construct(private readonly PaymentManager $paymentManager)
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

        if (! $price = Price::find($validated['price_id'])) {
            return ApiResource::error(
                message: 'Unable to find product price.',
                errors: ['price_id' => ['Unable to find product price.']],
                status: 400
            );
        }

        $order = Order::create([
            'user_id' => $user->id,
        ]);

        $order->items()->create([
            'product_id' => $price->product_id,
            'price_id' => $price->id,
        ]);

        $result = $this->paymentManager->startSubscription(
            user: $user,
            order: $order,
        );

        if (! $result) {
            return ApiResource::error(
                message: 'Unable to start subscription.',
                errors: ['price_id' => ['Unable to start subscription. Please try again later.']],
                status: 400
            );
        }

        return ApiResource::success(
            resource: [
                'checkout_url' => $result,
            ],
            message: 'Subscription started successfully.',
        );
    }
}
