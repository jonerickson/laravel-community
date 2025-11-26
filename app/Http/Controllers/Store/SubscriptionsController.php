<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\CommentData;
use App\Data\ProductData;
use App\Data\SubscriptionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\SubscriptionCancelRequest;
use App\Http\Requests\Store\SubscriptionCheckoutRequest;
use App\Http\Requests\Store\SubscriptionUpdateRequest;
use App\Managers\PaymentManager;
use App\Models\Product;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SubscriptionsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PaymentManager $paymentManager,
        private readonly CacheService $cache,
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {
        //
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Product::class);

        $subscriptions = collect($this->cache->getByKey('subscriptions.index'))
            ->filter(fn (array $product) => Gate::check('view', ProductData::from($product)))
            ->values();

        $subscriptionReviews = $subscriptions->mapWithKeys(function (array $product): array {
            $reviews = CommentData::collect(Product::with('reviews')
                ->findOrFail($product['id'])
                ->approvedReviews
                ->values()
                ->all(), PaginatedDataCollection::class);

            return [$product['id'] => $reviews->items()];
        });

        return Inertia::render('store/subscriptions', [
            'subscriptionProducts' => $subscriptions,
            'subscriptionReviews' => $subscriptionReviews,
            'currentSubscription' => $this->user instanceof User
                ? $this->paymentManager->currentSubscription($this->user)
                : null,
            'portalUrl' => $this->user instanceof User
                ? $this->paymentManager->getBillingPortalUrl($this->user)
                : null,
        ]);
    }

    public function store(SubscriptionCheckoutRequest $request): SymfonyResponse
    {
        $price = $request->getPrice();

        $this->authorize('view', $price->product);

        $currentSubscription = $this->paymentManager->currentSubscription($this->user);

        if (! $currentSubscription instanceof SubscriptionData) {
            $result = $this->paymentManager->startSubscription(
                order: $request->generateOrder($this->user),
            );

            if (! $result) {
                return back()->with('message', 'We were unable to start your subscription. Please try again later.');
            }

            return inertia()->location($result);
        }

        $result = $this->paymentManager->swapSubscription(
            user: $this->user,
            price: $price,
        );

        if (! $result) {
            return back()->with('message', 'We were unable to change your subscription. Please try again later.');
        }

        return back()->with('message', 'Your subscription was successfully updated.');
    }

    public function update(SubscriptionUpdateRequest $request): RedirectResponse
    {
        $price = $request->getPrice();

        $this->authorize('view', $price->product);

        $success = $this->paymentManager->continueSubscription($this->user);

        if ($success) {
            return back()->with('message', 'Your subscription has resumed successfully.');
        }

        return back()->with('message', 'We were unable to resume your subscription. Please try again later.');
    }

    public function destroy(SubscriptionCancelRequest $request): RedirectResponse
    {
        $price = $request->getPrice();

        $this->authorize('view', $price->product);

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

        return back()->with('message', $message);
    }
}
