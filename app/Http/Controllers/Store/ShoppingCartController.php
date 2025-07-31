<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

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
        return Inertia::render('store/shopping-cart', [
            'cartItems' => $this->cartService->getCartItems(),
            'cartCount' => $this->cartService->getCartCount(),
        ]);
    }

    public function destroy(): RedirectResponse
    {
        $this->cartService->clearCart();

        return redirect()->route('store.cart.index')->with('success', 'Cart cleared');
    }
}
