<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __invoke(ProductCategory $category)
    {
        return Inertia::render('store/categories/show', [
            'category' => $category,
            'products' => $category->products()->with(['prices', 'defaultPrice'])->get(),
        ]);
    }
}
