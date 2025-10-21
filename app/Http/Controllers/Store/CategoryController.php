<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\ProductCategoryData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', ProductCategory::class);

        $categories = ProductCategory::query()
            ->active()
            ->visible()
            ->ordered()
            ->with('image')
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

        $products = Product::query()
            ->whereHas('categories', fn (Builder $query) => $query->whereKey($category->id))
            ->approved()
            ->visible()
            ->with('defaultPrice')
            ->with(['prices' => function (HasMany $query): void {
                $query->active();
            }])
            ->where('is_subscription_only', false)
            ->ordered()
            ->get()
            ->values();

        return Inertia::render('store/categories/show', [
            'category' => ProductCategoryData::from($category),
            'products' => ProductData::collect($products),
        ]);
    }
}
