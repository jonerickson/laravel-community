<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\ProductCategoryData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', ProductCategory::class);

        return Inertia::render('store/categories/index', [
            'categories' => ProductCategoryData::collect(ProductCategory::query()
                ->with('image')
                ->get()
            ),
        ]);
    }

    public function show(ProductCategory $category)
    {
        $this->authorize('view', $category);

        $category->loadMissing('image');

        return Inertia::render('store/categories/show', [
            'category' => ProductCategoryData::from($category),
            'products' => ProductData::collect($category
                ->products()
                ->with('defaultPrice')
                ->with(['prices' => function (HasMany $query) {
                    $query->active();
                }])
                ->where('is_subscription_only', false)
                ->get()
            ),
        ]);
    }
}
