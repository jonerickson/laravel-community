<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __invoke(?ProductCategory $category = null, ?Product $product = null): Response
    {
        return Inertia::render('store/products/show', [
            'product' => $product->loadMissing(['prices', 'defaultPrice']),
            'category' => $category,
        ]);
    }
}
