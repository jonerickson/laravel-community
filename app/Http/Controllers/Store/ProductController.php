<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\CommentData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreProductRequest;
use App\Models\Comment;
use App\Models\Price;
use App\Models\Product;
use App\Services\ShoppingCartService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class ProductController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ShoppingCartService $cartService
    ) {
        //
    }

    public function store(StoreProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('view', $product);

        $priceId = $request->validated('price_id');
        $quantity = $request->validated('quantity', 1);

        if (! $priceId) {
            $defaultPrice = $product->defaultPrice;
            if ($defaultPrice) {
                $priceId = $defaultPrice->id;
            }
        }

        $this->cartService->addItem(
            priceId: $priceId,
            quantity: $quantity
        );

        return back()->with('message', 'The item was successfully added to your shopping cart.');
    }

    public function show(Request $request, Product $product): Response
    {
        $this->authorize('view', $product);

        $product->loadMissing(['inventoryItem', 'defaultPrice', 'images', 'seller.groups']);

        $reviews = CommentData::collect($product
            ->reviews()
            ->with('author.groups')
            ->with(['replies' => function (Comment|HasMany $query): void {
                $query->approved()->with(['author.groups'])->oldest();
            }])
            ->approved()
            ->latest()
            ->get()
            ->filter(fn (Comment $comment) => Gate::check('view', $comment))
            ->values()
            ->all(), PaginatedDataCollection::class);

        $product->load(['prices' => function (HasMany|Price $query): void {
            $query->active()->visible();
        }]);

        return Inertia::render('store/products/show', [
            'product' => ProductData::from($product),
            'reviews' => Inertia::scroll(fn () => $reviews->items()),
        ]);
    }
}
