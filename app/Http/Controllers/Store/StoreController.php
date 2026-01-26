<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\ProductCategoryData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('store/index', [
            'categories' => Inertia::defer(fn (): Collection => ProductCategoryData::collect(ProductCategory::query()
                ->whereNull('parent_id')
                ->active()
                ->visible()
                ->ordered()
                ->latest()
                ->take(3)
                ->get()
                ->filter(fn (ProductCategory $category) => Gate::check('view', $category))
                ->values()), 'categories'),
            'featuredProducts' => Inertia::defer(fn (): Collection => ProductData::collect(Product::query()
                ->products()
                ->approved()
                ->visible()
                ->active()
                ->featured()
                ->with('categories')
                ->with(['prices' => function (Price|HasMany $query): void {
                    $query->active()->visible();
                }])
                ->latest()
                ->take(6)
                ->get()
                ->filter(fn (Product $product) => Gate::check('view', $product))
                ->values()), 'featured'),
            'userProvidedProducts' => Inertia::defer(fn (): Collection => ProductData::collect(Product::query()
                ->products()
                ->marketplace()
                ->visible()
                ->active()
                ->approved()
                ->with(['categories', 'seller.groups'])
                ->with(['prices' => function (Price|HasMany $query): void {
                    $query->active()->visible();
                }])
                ->latest()
                ->take(5)
                ->get()
                ->filter(fn (Product $product) => Gate::check('view', $product))
                ->values()), 'community'),
        ]);
    }
}
