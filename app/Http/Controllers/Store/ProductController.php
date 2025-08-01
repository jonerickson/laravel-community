<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __invoke(Request $request, ?ProductCategory $category = null, ?Product $product = null): Response
    {
        $perPage = $request->input('per_page', 5);

        $reviews = $product->reviews()->latest()->paginate(
            perPage: $perPage
        );

        return Inertia::render('store/products/show', [
            'product' => $product->loadMissing(['prices', 'defaultPrice']),
            'reviews' => Inertia::defer(fn () => $reviews->items()),
            'reviewsPagination' => Arr::except($reviews->toArray(), ['data']),
            'category' => $category,
        ]);
    }
}
