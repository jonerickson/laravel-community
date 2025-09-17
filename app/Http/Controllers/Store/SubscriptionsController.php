<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\SubscriptionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelSubscriptionRequest;
use App\Http\Requests\Store\SubscriptionCheckoutRequest;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
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

    public function store(SubscriptionCheckoutRequest $request)
    {
        $user = Auth::user();
        $price = $request->getPrice();

        $order = Order::create([
            'user_id' => $user->id,
        ]);

        $order->items()->create([
            'product_id' => $price->product_id,
            'price_id' => $price->id,
        ]);

        $result = $this->paymentManager->startSubscription(
            user: $user,
            order: $order,
        );

        if (! $result) {
            return back()->with('message', 'Unable to start subscription. Please try again later.');
        }

        return Inertia::location($result);
    }

    public function destroy(CancelSubscriptionRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $price = $request->getPrice();

        $success = $this->paymentManager->cancelSubscription($user, $price);

        if ($success) {
            return to_route('store.subscriptions')
                ->with('message', 'Subscription cancelled successfully.');
        }

        return back()->with('message', 'Failed to cancel subscription.');
    }
}
