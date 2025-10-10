<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ProductData;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HomeController
{
    public function __invoke(): Response
    {
        return Inertia::render('home', [
            'subscriptions' => Inertia::defer(fn (): array|\Illuminate\Contracts\Pagination\CursorPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Support\Enumerable|\Spatie\LaravelData\CursorPaginatedDataCollection|\Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection => ProductData::collect($this->getSubscriptions())),
        ]);
    }

    protected function getSubscriptions()
    {
        return Product::query()
            ->subscriptions()
            ->with('prices')
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product));
    }
}
