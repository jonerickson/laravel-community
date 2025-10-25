<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\AddToCartRequest;
use App\Http\Requests\Api\Frontend\RemoveFromCartRequest;
use App\Http\Requests\Api\Frontend\UpdateCartRequest;
use App\Http\Resources\ApiResource;
use App\Services\ShoppingCartService;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingCartController extends Controller
{
    public function __construct(
        private readonly ShoppingCartService $cartService
    ) {
        //
    }

    public function store(AddToCartRequest $request): JsonResource
    {
        $cartResponse = $this->cartService->addItem(
            priceId: $request->integer('price_id'),
            quantity: $request->integer('quantity')
        );

        return ApiResource::created(
            resource: $cartResponse,
            message: 'The item was successfully added to your cart.'
        );
    }

    public function update(UpdateCartRequest $request): JsonResource
    {
        $cartResponse = $this->cartService->updateItem(
            priceId: $request->integer('price_id'),
            quantity: $request->integer('quantity')
        );

        return ApiResource::updated(
            resource: $cartResponse,
            message: 'Your cart has been successfully updated.'
        );
    }

    public function destroy(RemoveFromCartRequest $request): JsonResource
    {
        $cartResponse = $this->cartService->removeItem(
            priceId: $request->integer('price_id')
        );

        return ApiResource::success(
            resource: $cartResponse,
            message: 'The item was successfully removed from your cart.'
        );
    }
}
