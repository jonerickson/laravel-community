<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\CommentData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreProductRequest;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('view', $product);

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
        ])->with('message', 'The item was successfully added to your shopping cart.');
    }

    public function show(Request $request, Product $product): Response
    {
        $this->authorize('view', $product);

        $reviews = CommentData::collect($product
            ->reviews()
            ->latest()
            ->get()
            ->all(), PaginatedDataCollection::class);

        $product->load(['prices' => function (HasMany|Price $query): void {
            $query->active();
        }]);

        return Inertia::render('store/products/show', [
            'product' => ProductData::from($product),
            'reviews' => Inertia::scroll(fn () => $reviews->items()),
        ]);
    }
}
