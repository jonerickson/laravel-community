<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Enums\SubscriptionInterval;
use App\Http\Controllers\Controller;
use App\Managers\PaymentManager;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionsController extends Controller
{
    public function __construct(protected PaymentManager $paymentManager)
    {
        //
    }

    public function index(): Response
    {
        $user = Auth::user();

        $subscriptionProducts = Product::subscriptions()
            ->with('activePrices')
            ->with('categories')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($user): array {

                $isCurrentPlan = false;
                if ($user) {
                    $isCurrentPlan = $this->paymentManager->isSubscribedToProduct($user, $product);
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'slug' => $product->slug,
                    'featured_image_url' => $product->featured_image_url,
                    'prices' => $product->prices->keyBy('interval')->toArray(),
                    'features' => $product->metadata['features'] ?? [],
                    'popular' => $product->metadata['popular'] ?? false,
                    'current' => $isCurrentPlan,
                    'categories' => $product->categories->pluck('name')->toArray(),
                ];
            });

        return Inertia::render('store/subscriptions', [
            'subscriptionProducts' => $subscriptionProducts,
        ]);
    }
}
