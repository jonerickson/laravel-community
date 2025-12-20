<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ProductData;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HomeController
{
    public function __invoke(): Response
    {
        return Inertia::render('home', [
            'subscriptions' => Inertia::defer(fn (): Collection => ProductData::collect(Product::query()
                ->visible()
                ->active()
                ->subscriptions()
                ->with(['prices' => fn (HasMany|Price $query) => $query->recurring()->active()->visible()])
                ->ordered()
                ->get()
                ->filter(fn (Product $product) => Gate::check('view', $product))
            )),
        ]);
    }
}
