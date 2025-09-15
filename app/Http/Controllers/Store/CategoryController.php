<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('store/categories/index', [
            'categories' => ProductCategory::query()->with('image')->get(),
        ]);
    }

    public function show(ProductCategory $category)
    {
        $category->loadMissing('image');

        return Inertia::render('store/categories/show', [
            'category' => $category,
            'products' => $category->products()->with(['prices', 'defaultPrice'])->where('is_subscription_only', false)->get(),
        ]);
    }
}
