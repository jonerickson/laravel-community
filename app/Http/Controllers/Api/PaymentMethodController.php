<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApiResource;
use App\Managers\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController
{
    public function __construct(private readonly PaymentManager $paymentManager) {}

    public function create(): ApiResource
    {
        $user = Auth::guard('api')->user();

        return ApiResource::success(
            resource: $user->createSetupIntent()
        );
    }

    public function store(Request $request): ApiResource
    {
        $validated = $request->validate([
            'method' => 'string|required',
        ]);

        $user = Auth::guard('api')->user();

        $created = $this->paymentManager->createPaymentMethod(
            user: $user,
            paymentMethodId: $validated['method']
        );

        if (! $created) {
            return ApiResource::error(
                message: 'Payment method creation failed.',
                status: 404
            );
        }

        return ApiResource::success(
            resource: $created,
            message: 'Payment method successfully added.',
        );
    }

    public function destroy(Request $request): ApiResource
    {
        $validated = $request->validate([
            'method' => 'string|required',
        ]);

        $user = Auth::guard('api')->user();

        $deleted = $this->paymentManager->deletePaymentMethod(
            user: $user,
            paymentMethodId: $validated['method']
        );

        if (! $deleted) {
            return ApiResource::error(
                message: 'Payment method not found.',
                status: 404
            );
        }

        return ApiResource::success(
            message: 'Payment method successfully deleted.',
        );
    }

    public function update(Request $request): ApiResource
    {
        $validated = $request->validate([
            'method' => 'required|string',
            'is_default' => 'required|boolean',
        ]);

        $user = Auth::guard('api')->user();

        $updated = $this->paymentManager->updatePaymentMethod(
            user: $user,
            paymentMethodId: $validated['method'],
            isDefault: $validated['is_default']
        );

        if (! $updated) {
            return ApiResource::error(
                message: 'Payment method not found.',
                status: 404
            );
        }

        return ApiResource::success(
            message: 'Payment method updated successfully.',
        );
    }
}
