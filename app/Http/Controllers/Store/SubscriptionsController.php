<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionsController extends Controller
{
    public function index(): Response
    {
        $subscriptionProducts = Product::subscriptions()
            ->with('activePrices')
            ->with('categories')
            ->where('is_featured', true)
            ->orderBy('name')
            ->get()
            ->map(function (Product $product): array {
                $monthlyPrice = $product->activePrices
                    ->where('interval', 'month')
                    ->where('interval_count', 1)
                    ->first();

                $yearlyPrice = $product->activePrices
                    ->where('interval', 'year')
                    ->where('interval_count', 1)
                    ->first();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'slug' => $product->slug,
                    'featured_image_url' => $product->featured_image_url,
                    'pricing' => [
                        'monthly' => $monthlyPrice?->amount ?? 0,
                        'yearly' => $yearlyPrice?->amount ?? 0,
                    ],
                    'features' => $product->metadata['features'] ?? [],
                    'popular' => $product->metadata['popular'] ?? false,
                    'categories' => $product->categories->pluck('name')->toArray(),
                ];
            });

        return Inertia::render('store/subscriptions', [
            'subscriptionProducts' => $subscriptionProducts,
        ]);
    }
}
