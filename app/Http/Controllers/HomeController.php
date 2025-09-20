<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\SubscriptionData;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController
{
    public function __invoke(): Response
    {
        $subscriptions = Product::query()
            ->subscriptions()
            ->with('activePrices')
            ->orderBy('name')
            ->get();

        return Inertia::render('home', [
            'subscriptions' => Inertia::defer(fn () => SubscriptionData::collect($subscriptions)),
        ]);
    }
}
