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

    public function store(Request $request): JsonResource
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'price_id' => 'nullable|exists:product_prices,id',
            'quantity' => 'integer|min:1|max:99',
        ]);

        $product = Product::findOrFail($request->input('product_id'));
        $priceId = $request->input('price_id');
        $quantity = $request->input('quantity') ?? 1;

        if (! $priceId) {
            $defaultPrice = $product->defaultPrice;
            if ($defaultPrice) {
                $priceId = $defaultPrice->id;
            }
        }

        $cart = Session::get('shopping_cart', []);
        $cartKey = $product->id.($priceId ? '_'.$priceId : '');

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $product->id,
                'price_id' => $priceId,
                'name' => $product->name,
                'slug' => $product->slug,
                'quantity' => $quantity,
                'added_at' => now()->toISOString(),
            ];
        }

        Session::put('shopping_cart', $cart);

        $cartItems = $this->cartService->getCartItems();

        return ApiResource::success(
            resource: [
                'cartItems' => $cartItems,
                'cartCount' => count($cartItems),
            ],
            message: 'Product added to cart successfully'
        );
    }

    public function update(Request $request, Product $product): JsonResource
    {
        $request->validate([
            'price_id' => 'nullable|exists:product_prices,id',
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $cart = Session::get('shopping_cart', []);
        $priceId = $request->input('price_id');
        $cartKey = $product->getKey().($priceId ? '_'.$priceId : '');

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = $request->input('quantity');
            Session::put('shopping_cart', $cart);
        }

        $cartItems = $this->cartService->getCartItems();

        return ApiResource::updated(
            resource: [
                'cartItems' => $cartItems,
                'cartCount' => count($cartItems),
            ],
            message: 'Cart updated successfully'
        );
    }

    public function delete(Request $request, Product $product): JsonResource
    {
        $cart = Session::get('shopping_cart', []);
        $priceId = $request->input('price_id');
        $cartKey = $product->getKey().($priceId ? '_'.$priceId : '');

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
            message: 'Item removed from cart successfully'
        );
    }
}
