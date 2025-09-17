<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreProductRequest;
use App\Models\Product;
use App\Services\ShoppingCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ShoppingCartService $cartService
    ) {}

    public function store(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        $priceId = $validated['price_id'];
        $quantity = $validated['quantity'] ?? 1;

        if (! $priceId) {
            $defaultPrice = $product->defaultPrice;
            if ($defaultPrice) {
                $priceId = $defaultPrice->id;
            }
        }

        $cart = Session::get('shopping_cart', []);
        $cartKey = $product->id.'_'.$priceId;

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'product_id' => $product->id,
                'price_id' => $priceId,
                'name' => $product->name,
                'slug' => $product->slug,
                'quantity' => $quantity,
                'added_at' => now(),
            ];
        }

        Session::put('shopping_cart', $cart);

        return to_route('store.products.show', [
            'product' => $product,
        ]);
    }

    public function show(Request $request, Product $product): Response
    {
        $perPage = $request->input('per_page', 5);

        $reviews = $product->reviews()->latest()->paginate(
            perPage: $perPage
        );

        return Inertia::render('store/products/show', [
            'product' => $product->loadMissing(['prices', 'defaultPrice']),
            'reviews' => Inertia::defer(fn () => $reviews->items()),
            'reviewsPagination' => Arr::except($reviews->toArray(), ['data']),
        ]);
    }
}
