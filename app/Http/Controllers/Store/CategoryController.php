<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('store/categories');
    }
}
