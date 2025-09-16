<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\SubscriptionData;
use App\Http\Controllers\Controller;
use App\Managers\PaymentManager;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionsController extends Controller
{
    public function __construct(private readonly PaymentManager $paymentManager)
    {
        //
    }

    public function index(): Response
    {
        $user = Auth::user();

        $subscriptionProducts = Product::query()
            ->subscriptions()
            ->with('activePrices')
            ->with('categories')
            ->with('policies.category')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) use ($user): SubscriptionData {

                $isCurrentPlan = false;
                if ($user) {
                    $isCurrentPlan = $this->paymentManager->isSubscribedToProduct($user, $product);
                }

                $subscriptionData = SubscriptionData::from($product);
                $subscriptionData->current = $isCurrentPlan;

                return $subscriptionData;
            });

        return Inertia::render('store/subscriptions', [
            'subscriptionProducts' => $subscriptionProducts,
        ]);
    }
}
