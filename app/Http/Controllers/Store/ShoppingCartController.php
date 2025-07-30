<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\Product;
use App\Models\ProductPrice;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class ShoppingCartController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('store/shopping-cart', [
            'cartItems' => $cartItems = $this->getCartItems(),
            'cartCount' => count($cartItems),
        ]);
    }

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

        // If no price_id provided, use the default price
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

        $cartItems = $this->getCartItems();

        return ApiResource::success([
            'cartItems' => $cartItems,
            'cartCount' => count($cartItems),
        ], 'Product added to cart successfully');
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

        $cartItems = $this->getCartItems();

        return ApiResource::updated([
            'cartItems' => $cartItems,
            'cartCount' => count($cartItems),
        ], 'Cart updated successfully');
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

        $cartItems = $this->getCartItems();

        return ApiResource::success([
            'cartItems' => $cartItems,
            'cartCount' => count($cartItems),
        ], 'Item removed from cart successfully');
    }

    public function clear(): RedirectResponse
    {
        Session::forget('shopping_cart');

        return redirect()->route('store.cart')->with('success', 'Cart cleared');
    }

    public function checkout(Request $request): JsonResource
    {
        $user = Auth::user();
        if (! $user) {
            return ApiResource::error('Authentication required to checkout', ['auth' => ['User must be authenticated.']], 401);
        }

        $cartItems = $this->getCartItems();
        if (empty($cartItems)) {
            return ApiResource::error('Cart is empty', ['cart' => ['Cart cannot be empty.']], 400);
        }

        $lineItems = [];
        foreach ($cartItems as $item) {
            $product = $item['product'];
            if (! $product || ! $product->stripe_product_id) {
                return ApiResource::error("{$item['name']} is not available for purchase.", [
                    'product' => ["{$item['name']} is not configured for purchase."],
                ], 400);
            }

            $selectedPrice = null;
            if ($item['price_id']) {
                $selectedPrice = $product->prices()->where('id', $item['price_id'])->first();
            }

            if (! $selectedPrice) {
                $selectedPrice = $product->defaultPrice;
            }

            if (! $selectedPrice || ! $selectedPrice->stripe_price_id) {
                return ApiResource::error("Price not configured for {$item['name']}", [
                    'price' => ["Price not configured for {$item['name']}."],
                ], 400);
            }

            $lineItems[] = [
                'price' => $selectedPrice->stripe_price_id,
                'quantity' => $item['quantity'],
            ];
        }

        try {
            $checkout = $user->checkout($lineItems, [
                'success_url' => route('store.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('store.cart'),
                'metadata' => [
                    'cart_items' => json_encode(array_map(fn ($item) => [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ], $cartItems)),
                ],
            ]);

            return ApiResource::success([
                'checkout_url' => $checkout->url,
            ], 'Checkout session created successfully', [
                'session_id' => $checkout->id,
            ]);
        } catch (Exception $e) {
            return ApiResource::error('Failed to create checkout session: '.$e->getMessage(), [
                'checkout' => [$e->getMessage()],
            ]);
        }
    }

    private function getCartItems(): array
    {
        $cart = Session::get('shopping_cart', []);
        if (empty($cart)) {
            return [];
        }

        $productIds = array_unique(array_column($cart, 'product_id'));
        $priceIds = array_filter(array_column($cart, 'price_id'));

        $products = Product::with(['prices' => function ($query) {
            $query->where('is_active', true)->orderBy('is_default', 'desc');
        }, 'defaultPrice'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $prices = ! empty($priceIds)
            ? ProductPrice::whereIn('id', $priceIds)->get()->keyBy('id')
            : collect();

        return collect($cart)->map(function ($item) use ($products, $prices) {
            $product = $products->get($item['product_id']);
            $selectedPrice = $item['price_id'] ? $prices->get($item['price_id']) : null;

            return [
                'product_id' => $item['product_id'],
                'price_id' => $item['price_id'],
                'name' => $product?->name ?? $item['name'],
                'slug' => $product?->slug ?? $item['slug'],
                'quantity' => $item['quantity'],
                'product' => $product,
                'selected_price' => $selectedPrice,
                'available_prices' => $product?->prices ?? collect(),
                'added_at' => $item['added_at'],
            ];
        })->values()->toArray();
    }
}
