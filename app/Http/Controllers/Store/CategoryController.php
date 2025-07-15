<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('store/categories', [
            'categories' => ProductCategory::query()->select(['id', 'name', 'slug'])->latest()->take(5)->get(),
        ]);
    }
}
