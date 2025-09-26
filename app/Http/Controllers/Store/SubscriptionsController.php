<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\SubscriptionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\SubscriptionCancelRequest;
use App\Http\Requests\Store\SubscriptionCheckoutRequest;
use App\Http\Requests\Store\SubscriptionUpdateRequest;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

        $subscriptions = Product::query()
            ->subscriptions()
            ->with('activePrices')
            ->with('categories')
            ->with('policies.category')
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->values();

        return Inertia::render('store/subscriptions', [
            'subscriptionProducts' => SubscriptionData::collect($subscriptions),
            'currentSubscription' => $user ? $this->paymentManager->currentSubscription($user) : null,
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

        if ($result === false || ($result === '' || $result === '0')) {
            return back()
                ->with('message', 'We were unable to start your subscription. Please try again later.');
        }

        if ($result === true) {
            return to_route('store.subscriptions')
                ->with('message', 'Your subscription was successfully updated.');
        }

        return Inertia::location($result);
    }

    public function update(SubscriptionUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $success = $this->paymentManager->continueSubscription($user);

        if ($success) {
            return to_route('store.subscriptions')
                ->with('message', 'Your subscription has resumed successfully.');
        }

        return back()->with('message', 'We were unable to resume your subscription. Please try again later.');
    }

    public function destroy(SubscriptionCancelRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $immediate = $request->isImmediate();

        $success = $this->paymentManager->cancelSubscription(
            user: $user,
            cancelNow: $immediate
        );

        if (! $success) {
            return back()->with('message', 'Your subscription failed to cancel. Please try again later.');
        }

        $message = $immediate
            ? 'Your subscription has been cancelled immediately.'
            : 'Your subscription has been scheduled to cancel at the end of the billing cycle.';

        return to_route('store.subscriptions')
            ->with('message', $message);
    }
}
