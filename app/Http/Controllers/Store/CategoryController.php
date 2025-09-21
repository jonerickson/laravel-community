<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\ProductCategoryData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
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
            ->with('image')
            ->get()
            ->filter(fn (ProductCategory $category) => Gate::check('view', $category));

        return Inertia::render('store/categories/index', [
            'categories' => ProductCategoryData::collect($categories),
        ]);
    }

    public function show(ProductCategory $category)
    {
        $this->authorize('view', $category);

        $products = $category
            ->products()
            ->with('defaultPrice')
            ->with(['prices' => function (HasMany $query) {
                $query->active();
            }])
            ->where('is_subscription_only', false)
            ->get();

        return Inertia::render('store/categories/show', [
            'category' => ProductCategoryData::from($category),
            'products' => ProductData::collect($products),
        ]);
    }
}
