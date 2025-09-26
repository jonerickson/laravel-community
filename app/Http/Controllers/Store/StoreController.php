<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\ProductCategoryData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('store/index', [
            'categories' => ProductCategoryData::collect(ProductCategory::query()
                ->with('image')
                ->latest()
                ->take(5)
                ->get()
                ->filter(fn (ProductCategory $category) => Gate::check('view', $category))
                ->values()),
            'featuredProducts' => ProductData::collect(Product::query()
                ->products()
                ->featured()
                ->with('categories')
                ->with(['prices' => function (HasMany $query): void {
                    $query->active();
                }])
                ->latest()
                ->take(6)
                ->get()
                ->filter(fn (Product $product) => Gate::check('view', $product))
                ->values()),
            'userProvidedProducts' => [],
        ]);
    }
}
