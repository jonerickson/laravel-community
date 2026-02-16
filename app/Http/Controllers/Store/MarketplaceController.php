<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\PaginatedData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\LaravelData\PaginatedDataCollection;

class MarketplaceController extends Controller
{
    use AuthorizesRequests;

    public function __invoke()
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->marketplace()
            ->approved()
            ->visible()
            ->active()
            ->with(['defaultPrice', 'inventoryItem', 'seller.groups'])
            ->with(['prices' => function (Price|HasMany $query): void {
                $query->active()->visible();
            }])
            ->where('is_subscription_only', false)
            ->ordered()
            ->paginate(perPage: 12);

        $filteredProducts = $products
            ->collect()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->values();

        return Inertia::render('store/marketplace/index', [
            'products' => PaginatedData::from(ProductData::collect($products->setCollection($filteredProducts), PaginatedDataCollection::class)->items()),
        ]);
    }
}
