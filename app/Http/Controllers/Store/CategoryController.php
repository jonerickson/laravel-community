<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\PaginatedData;
use App\Data\ProductCategoryData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\LaravelData\PaginatedDataCollection;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::query()
            ->whereNull('parent_id')
            ->active()
            ->visible()
            ->ordered()
            ->get()
            ->filter(fn (ProductCategory $category) => Gate::check('view', $category))
            ->values();

        return Inertia::render('store/categories/index', [
            'categories' => ProductCategoryData::collect($categories),
        ]);
    }

    public function show(ProductCategory $category)
    {
        $this->authorize('view', $category);

        $category->load(['parent', 'children']);

        $products = Product::query()
            ->whereHas('categories', fn (Builder $query) => $query->whereKey($category->id))
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

        return Inertia::render('store/categories/show', [
            'category' => ProductCategoryData::from($category),
            'products' => PaginatedData::from(ProductData::collect($products->setCollection($filteredProducts), PaginatedDataCollection::class)->items()),
        ]);
    }
}
