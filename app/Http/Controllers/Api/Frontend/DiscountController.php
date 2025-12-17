<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Enums\DiscountValueType;
use App\Http\Requests\Api\Frontend\ApplyDiscountRequest;
use App\Http\Requests\Api\Frontend\RemoveDiscountRequest;
use App\Http\Resources\ApiResource;
use App\Models\Discount;
use App\Models\Order;
use App\Services\DiscountService;
use App\Services\ShoppingCartService;
use Illuminate\Support\Number;
use Throwable;

class DiscountController
{
    public function __construct(
        private readonly DiscountService $discountService,
        private readonly ShoppingCartService $cartService,
    ) {
        //
    }

    public function store(ApplyDiscountRequest $request): ApiResource
    {
        $code = $request->validated('code');

        $order = $this->cartService->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return ApiResource::error(
                message: 'Unable to create order.',
                errors: ['order' => ['Failed to create order.']],
                status: 400
            );
        }

        $discount = $this->discountService->validateDiscount($code);

        if (! $discount instanceof Discount) {
            return ApiResource::error(
                message: 'Invalid or expired discount code.',
                errors: ['code' => ['The discount code is either invalid, has inadequate funds or has expired.']],
                status: 422
            );
        }

        try {
            $this->cartService->applyDiscount($order, $discount);
        } catch (Throwable $throwable) {
            return ApiResource::error(
                message: $throwable->getMessage(),
                errors: ['code' => [$throwable->getMessage()]],
                status: 422
            );
        }

        $discountValue = $discount->discount_type === DiscountValueType::Percentage
            ? $discount->value.'%'
            : Number::currency($discount->value);

        return ApiResource::success(
            resource: [
                'code' => $discount->code,
                'type' => $discount->type->value,
                'discount_type' => $discount->discount_type->value,
                'discount_value' => $discountValue,
            ],
            message: 'The discount code was applied successfully.',
        );
    }

    public function destroy(RemoveDiscountRequest $request): ApiResource
    {
        $order = $this->cartService->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return ApiResource::error(
                message: 'Unable to find order.',
                errors: ['order' => ['No pending order found.']],
                status: 400
            );
        }

        $discountId = $request->integer('discount_id');

        $this->cartService->removeDiscount($order, $discountId);

        return ApiResource::success(
            message: 'The discount was removed successfully.',
        );
    }
}
