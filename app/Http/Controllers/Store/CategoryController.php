<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('store/categories', [
            'categories' => ProductCategory::query()
                ->latest()
                ->take(5)
                ->get(),
            'featuredProducts' => Product::query()
                ->featured()
                ->with(['categories'])
                ->latest()
                ->take(6)
                ->get(),
            'userProvidedProducts' => [],
        ]);
    }

    public function show(ProductCategory $category)
    {
        return back();
    }
}
