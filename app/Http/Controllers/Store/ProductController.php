<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __invoke(Product $product): Response
    {
        return Inertia::render('store/product', [
            'product' => $product,
        ]);
    }
}
