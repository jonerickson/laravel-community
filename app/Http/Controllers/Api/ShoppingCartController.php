<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Product;
use App\Services\ShoppingCartService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Session;

class ShoppingCartController extends Controller
{
    public function __construct(
        private readonly ShoppingCartService $cartService
    ) {}

    public function update(Request $request): JsonResource
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'price_id' => 'nullable|exists:products_prices,id',
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $cart = Session::get('shopping_cart', []);
        $productId = $request->input('product_id');
        $priceId = $request->input('price_id');
        $quantity = $request->input('quantity');

        $existingKeys = array_filter(array_keys($cart), fn (int|string $key): bool => str_starts_with($key, $productId.'_'));

        foreach ($existingKeys as $key) {
            unset($cart[$key]);
        }

        $product = Product::findOrFail($productId);
        $newCartKey = $productId.'_'.$priceId;

        $cart[$newCartKey] = [
            'product_id' => $productId,
            'price_id' => $priceId,
            'name' => $product->name,
            'slug' => $product->slug,
            'quantity' => $quantity,
            'added_at' => now()->toISOString(),
        ];

        Session::put('shopping_cart', $cart);

        $cartItems = $this->cartService->getCartItems();

        return ApiResource::updated(
            resource: [
                'cartItems' => $cartItems,
                'cartCount' => count($cartItems),
            ],
            message: 'Cart updated successfully.'
        );
    }

    public function destroy(Request $request): JsonResource
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'price_id' => 'nullable|exists:products_prices,id',
        ]);

        $cart = Session::get('shopping_cart', []);
        $productId = $request->input('product_id');
        $priceId = $request->input('price_id');
        $cartKey = $productId.'_'.$priceId;

        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            Session::put('shopping_cart', $cart);
        }

        $cartItems = $this->cartService->getCartItems();

        return ApiResource::success(
            resource: [
                'cartItems' => $cartItems,
                'cartCount' => count($cartItems),
            ],
            message: 'Item removed from cart successfully.'
        );
    }
}
