<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\CommentData;
use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\SubscriptionCancelRequest;
use App\Http\Requests\Store\SubscriptionCheckoutRequest;
use App\Http\Requests\Store\SubscriptionUpdateRequest;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class SubscriptionsController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
        private readonly PaymentManager $paymentManager
    ) {
        //
    }

    public function index(): Response
    {
        $subscriptions = Product::query()
            ->subscriptions()
            ->visible()
            ->with('prices')
            ->with('categories')
            ->with('policies.category')
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->values();

        $subscriptionReviews = $subscriptions->mapWithKeys(function (Product $product): array {
            $reviews = CommentData::collect($product
                ->reviews()
                ->latest()
                ->get()
                ->all(), PaginatedDataCollection::class);

            return [$product->id => $reviews->items()];
        });

        return Inertia::render('store/subscriptions', [
            'subscriptionProducts' => ProductData::collect($subscriptions),
            'subscriptionReviews' => $subscriptionReviews,
            'currentSubscription' => $this->user ? $this->paymentManager->currentSubscription($this->user) : null,
        ]);
    }

    public function store(SubscriptionCheckoutRequest $request)
    {
        $price = $request->getPrice();

        $order = Order::create([
            'user_id' => $this->user->id,
        ]);

        $order->items()->create([
            'product_id' => $price->product_id,
            'price_id' => $price->id,
        ]);

        $result = $this->paymentManager->startSubscription(
            order: $order,
        );

        if (! $result) {
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
        $success = $this->paymentManager->continueSubscription($this->user);

        if ($success) {
            return to_route('store.subscriptions')
                ->with('message', 'Your subscription has resumed successfully.');
        }

        return back()->with('message', 'We were unable to resume your subscription. Please try again later.');
    }

    public function destroy(SubscriptionCancelRequest $request): RedirectResponse
    {
        $immediate = $request->isImmediate();

        $success = $this->paymentManager->cancelSubscription(
            user: $this->user,
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
