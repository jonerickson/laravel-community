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
            'categories' => Inertia::defer(fn (): \Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection|\Spatie\LaravelData\CursorPaginatedDataCollection|\Illuminate\Support\Enumerable|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Contracts\Pagination\CursorPaginator|array => ProductCategoryData::collect(ProductCategory::query()
                ->active()
                ->visible()
                ->ordered()
                ->with('image')
                ->latest()
                ->take(4)
                ->get()
                ->filter(fn (ProductCategory $category) => Gate::check('view', $category))
                ->values())),
            'featuredProducts' => Inertia::defer(fn (): \Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection|\Spatie\LaravelData\CursorPaginatedDataCollection|\Illuminate\Support\Enumerable|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Contracts\Pagination\CursorPaginator|array => ProductData::collect(Product::query()
                ->products()
                ->approved()
                ->visible()
                ->featured()
                ->with('categories')
                ->with(['prices' => function (HasMany $query): void {
                    $query->active();
                }])
                ->latest()
                ->take(6)
                ->get()
                ->filter(fn (Product $product) => Gate::check('view', $product))
                ->values())),
            'userProvidedProducts' => Inertia::defer(fn (): \Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection|\Spatie\LaravelData\CursorPaginatedDataCollection|\Illuminate\Support\Enumerable|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Contracts\Pagination\CursorPaginator|array => ProductData::collect(Product::query()
                ->products()
                ->marketplace()
                ->visible()
                ->approved()
                ->with('categories')
                ->with(['prices' => function (HasMany $query): void {
                    $query->active();
                }])
                ->latest()
                ->take(5)
                ->get()
                ->filter(fn (Product $product) => Gate::check('view', $product))
                ->values())),
        ]);
    }
}
