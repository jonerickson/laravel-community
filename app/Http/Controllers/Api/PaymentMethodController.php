<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\PaymentSetupIntentData;
use App\Http\Resources\ApiResource;
use App\Managers\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController
{
    public function __construct(private readonly PaymentManager $paymentManager) {}

    public function create(): ApiResource
    {
        $user = Auth::user();
        $setupIntent = $user->createSetupIntent();

        $setupIntentData = PaymentSetupIntentData::from([
            'id' => $setupIntent->id,
            'clientSecret' => $setupIntent->client_secret,
            'status' => $setupIntent->status,
            'customer' => $setupIntent->customer,
            'paymentMethodTypes' => $setupIntent->payment_method_types,
            'usage' => $setupIntent->usage,
        ]);

        return ApiResource::success(
            resource: $setupIntentData
        );
    }

    public function store(Request $request): ApiResource
    {
        $validated = $request->validate([
            'method' => 'string|required',
        ]);

        $user = Auth::user();

        $created = $this->paymentManager->createPaymentMethod(
            user: $user,
            paymentMethodId: $validated['method']
        );

        if (blank($created)) {
            return ApiResource::error(
                message: 'The payment method creation failed. Please try again later.',
                status: 404
            );
        }

        return ApiResource::success(
            resource: $created,
            message: 'Your payment method was successfully added.',
        );
    }

    public function destroy(Request $request): ApiResource
    {
        $validated = $request->validate([
            'method' => 'string|required',
        ]);

        $user = Auth::user();

        $deleted = $this->paymentManager->deletePaymentMethod(
            user: $user,
            paymentMethodId: $validated['method']
        );

        if (! $deleted) {
            return ApiResource::error(
                message: 'The payment method was not found. Please try again later.',
                status: 404
            );
        }

        return ApiResource::success(
            message: 'The payment method was successfully deleted.',
        );
    }

    public function update(Request $request): ApiResource
    {
        $validated = $request->validate([
            'method' => 'required|string',
            'is_default' => 'required|boolean',
        ]);

        $user = Auth::user();

        $updated = $this->paymentManager->updatePaymentMethod(
            user: $user,
            paymentMethodId: $validated['method'],
            isDefault: $validated['is_default']
        );

        if (! $updated) {
            return ApiResource::error(
                message: 'The payment method was not found. Please try again later.',
                status: 404
            );
        }

        return ApiResource::success(
            message: 'The payment method was updated successfully.',
        );
    }
}
