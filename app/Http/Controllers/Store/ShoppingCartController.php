<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\DiscountData;
use App\Data\OrderData;
use App\Http\Controllers\Controller;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Services\ShoppingCartService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingCartController extends Controller
{
    public function __construct(
        private readonly ShoppingCartService $cartService,
        private readonly PaymentManager $paymentManager,
    ) {}

    public function index(): Response
    {
        $cart = $this->cartService->getCart();
        $order = $this->cartService->getOrCreatePendingOrder();

        return Inertia::render('store/shopping-cart', [
            'cartItems' => $cart->cartItems,
            'cartCount' => $cart->cartCount,
            'order' => Inertia::defer(function () use ($order): ?array {
                if (! $order instanceof Order) {
                    return null;
                }

                $discounts = $order->discounts->map(fn ($discount): array => array_merge(
                    DiscountData::from($discount)->toArray(),
                    [
                        'amountApplied' => $discount->pivot->amount_applied,
                        'balanceBefore' => $discount->pivot->balance_before,
                        'balanceAfter' => $discount->pivot->balance_after,
                    ]
                ))->toArray();
                $data = OrderData::from($order)->toArray();
                $data['discounts'] = $discounts;

                return $data;
            }),
        ]);
    }

    public function destroy(): RedirectResponse
    {
        $order = $this->cartService->getOrCreatePendingOrder();

        $this->paymentManager->cancelOrder($order);
        $this->cartService->clearCart();

        return back()->with('message', 'Your cart has been successfully emptied.');
    }
}
