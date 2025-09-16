<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\CartItemData;
use App\Http\Controllers\Controller;
use App\Services\ShoppingCartService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingCartController extends Controller
{
    public function __construct(
        private readonly ShoppingCartService $cartService
    ) {}

    public function index(): Response
    {
        $cartItems = $this->cartService->getCartItems();

        return Inertia::render('store/shopping-cart', [
            'cartItems' => CartItemData::collect($cartItems),
            'cartCount' => $this->cartService->getCartCount(),
        ]);
    }

    public function destroy(): RedirectResponse
    {
        $this->cartService->clearCart();

        return to_route('store.cart.index')->with('success', 'Cart cleared');
    }
}
