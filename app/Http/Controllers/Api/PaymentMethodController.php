<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController
{
    public function create(): ApiResource
    {
        $user = Auth::guard('api')->user();

        return ApiResource::success(
            resource: $user->createSetupIntent()
        );
    }

    public function destroy(Request $request): ApiResource
    {
        $validated = $request->validate([
            'method' => 'string',
        ]);

        $user = Auth::guard('api')->user();

        if (! $user->findPaymentMethod($validated['method'])) {
            return ApiResource::error(
                message: 'Payment method not found.',
                status: 404
            );
        }

        $user->deletePaymentMethod($validated['method']);

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

        if (! $user->findPaymentMethod($validated['method'])) {
            return ApiResource::error(
                message: 'Payment method not found.',
                status: 404
            );
        }

        if ($validated['is_default']) {
            $user->updateDefaultPaymentMethod($validated['method']);
        }

        return ApiResource::success(
            message: 'Payment method updated successfully.',
        );
    }
}
