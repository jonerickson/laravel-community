<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\CheckoutData;
use App\Data\CustomerData;
use App\Enums\OrderStatus;
use App\Http\Resources\ApiResource;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ShoppingCartService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Throwable;

class CheckoutController
{
    public function __construct(
        private readonly ShoppingCartService $cartService,
        private readonly PaymentManager $paymentManager,
        private readonly InventoryService $inventoryService,
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {}

    public function __invoke(Request $request): ApiResource
    {
        if (! $this->user instanceof User) {
            return ApiResource::error(
                message: 'Authentication is required to checkout.',
                errors: ['auth' => ['User must be authenticated.']],
                status: 401
            );
        }

        $result = true;
        if (! $this->paymentManager->getCustomer($this->user) instanceof CustomerData) {
            $result = $this->paymentManager->createCustomer($this->user);
        }

        if (! $result) {
            return ApiResource::error(
                message: 'Unable to create/fetch your customer account.',
                errors: ['auth' => ['User must be customer.']],
                status: 401
            );
        }

        $cart = $this->cartService->getCart();

        if (blank($cart->cartItems)) {
            return ApiResource::error(
                message: 'Your cart is currently empty.',
                errors: ['cart' => ['Cart cannot be empty.']],
                status: 400
            );
        }

        $order = $this->cartService->getOrCreatePendingOrder();

        if (! $order instanceof Order) {
            return ApiResource::error(
                message: 'Failed to create order.',
                errors: ['order' => ['Unable to create order.']],
            );
        }

        foreach ($cart->cartItems as $item) {
            if ($item->product->inventoryItem?->trackInventory) {
                if ($item->product->inventoryItem->quantityAvailable <= 0 && ! $item->product->inventoryItem->allowBackorder) {
                    return ApiResource::error(
                        message: sprintf('There is not enough stock available for %s. Please adjust your quantity and try again.', $item->name),
                        errors: ['inventory' => [sprintf('There is not enough stock available for %s. Please adjust your quantity and try again.', $item->name)]],
                        status: 400
                    );
                }

                try {
                    $this->inventoryService->reserveInventory($order);
                } catch (Throwable $e) {
                    return ApiResource::error(
                        message: sprintf('Failed to reserve inventory for %s: %s', $item->name, $e->getMessage()),
                        errors: ['inventory' => [sprintf('Could not reserve %s.', $item->name)]],
                        status: 400
                    );
                }
            }
        }

        if ($order->amount <= 0) {
            $order->update([
                'status' => OrderStatus::Succeeded,
                'amount_paid' => 0,
                'amount_remaining' => 0,
                'amount_overpaid' => 0,
                'amount_due' => 0,
            ]);

            $this->cartService->clearCart();

            $checkoutData = CheckoutData::from([
                'checkoutUrl' => route('settings.orders'),
            ]);

            return ApiResource::success(
                resource: $checkoutData,
                message: 'Your order was completed successfully.',
            );
        }

        foreach ($cart->cartItems as $item) {
            if (! $item->product || ! $item->product->externalProductId) {
                return ApiResource::error(
                    message: $item->name.' is not available for purchase.',
                    errors: ['product' => [$item->name.' is not configured for purchase.']],
                    status: 400
                );
            }

            $selectedPrice = $item->selectedPrice ?? $item->product->defaultPrice;

            if (! $selectedPrice || ! $selectedPrice->externalPriceId) {
                return ApiResource::error(
                    message: sprintf('No prices are configured for %s.', $item->name),
                    errors: ['price' => [sprintf('No prices are configured for %s.', $item->name)]],
                    status: 400
                );
            }
        }

        $result = $this->paymentManager->getCheckoutUrl(
            order: $order,
        );

        if (! is_string($result)) {
            return ApiResource::error(
                message: 'Failed to create checkout session. Please try again.',
            );
        }

        $checkoutData = CheckoutData::from([
            'checkoutUrl' => $result,
        ]);

        return ApiResource::success(
            resource: $checkoutData,
        );
    }
}
