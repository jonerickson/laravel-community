<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Data\CommentData;
use App\Data\ProductData;
use App\Data\SubscriptionData;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\SubscriptionCancelRequest;
use App\Http\Requests\Store\SubscriptionCheckoutRequest;
use App\Http\Requests\Store\SubscriptionUpdateRequest;
use App\Managers\PaymentManager;
use App\Models\Comment;
use App\Models\Discount;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use App\Services\DiscountService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        private readonly DiscountService $discountService,
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {
        //
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Product::class);

        $subscriptions = Product::query()
            ->subscriptions()
            ->visible()
            ->active()
            ->with(['approvedReviews' => fn (MorphMany|Comment $query) => $query->latest()])
            ->with(['prices' => fn (HasMany|Price $query) => $query->recurring()->active()->visible()])
            ->with(['categories', 'policies.category', 'defaultPrice'])
            ->ordered()
            ->get()
            ->filter(fn (Product $product) => Gate::check('view', $product))
            ->values();

        $subscriptionReviews = $subscriptions->mapWithKeys(function (Product $product): array {
            $reviews = CommentData::collect($product
                ->approvedReviews
                ->values()
                ->all(), PaginatedDataCollection::class);

            return [$product->getKey() => $reviews->items()];
        });

        $currentSubscription = $this->user instanceof User
            ? $this->paymentManager->currentSubscription($this->user)
            : null;

        return Inertia::render('store/subscriptions', [
            'subscriptionProducts' => ProductData::collect($subscriptions),
            'subscriptionReviews' => $subscriptionReviews,
            'currentSubscription' => $currentSubscription,
            'portalUrl' => $this->user instanceof User
                ? $this->paymentManager->getBillingPortalUrl($this->user)
                : null,
            'offerAvailable' => $this->user instanceof User && $currentSubscription instanceof SubscriptionData && $this->discountService->cancellationOfferIsAvailable($this->user, $currentSubscription),
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
            prorationBehavior: ProrationBehavior::AlwaysInvoice,
            paymentBehavior: PaymentBehavior::ErrorIfIncomplete,
        );

        if (! $result) {
            return back()->with('message', 'We were unable to update your subscription. Please make sure you have a valid payment method on file and try again.');
        }

        return back()->with('message', 'Your subscription was successfully updated.');
    }

    public function update(SubscriptionUpdateRequest $request): RedirectResponse
    {
        /** @var RedirectResponse $response */
        $response = value(match ($request->validated('action')) {
            'continue' => function () use ($request) {
                $price = $request->getPrice();

                $this->authorize('view', $price->product);

                $success = $this->paymentManager->continueSubscription($this->user);

                if ($success) {
                    return back()->with('message', 'Your subscription has resumed successfully.');
                }

                return back()->with('message', 'We were unable to resume your subscription. Please try again later.');
            },
            'offer' => function () {
                $discount = tap($this->discountService->createCancellationOffer(
                    user: $this->user,
                ), function (Discount $discount): void {
                    $coupon = $this->paymentManager->createCoupon($discount);

                    $discount->update([
                        'external_coupon_id' => $coupon->externalCouponId,
                    ]);
                });

                if (! $discount instanceof Discount) {
                    return back()->with('message', 'We were unable to apply your exclusive offer. Please try again later.');
                }

                $this->paymentManager->updateSubscription($this->user, [
                    'discounts' => [
                        ['coupon' => $discount->external_coupon_id],
                    ],
                    'proration_behavior' => ProrationBehavior::None->value,
                ]);

                return back()->with('message', 'Your offer has been successfully applied. Any open invoices will be automatically charged at the normal billing cycle date.');
            }
        });

        return $response;
    }

    public function destroy(SubscriptionCancelRequest $request): RedirectResponse
    {
        $price = $request->getPrice();

        $this->authorize('view', $price->product);

        $immediate = $request->isImmediate();

        $success = $this->paymentManager->cancelSubscription(
            user: $this->user,
            cancelNow: $immediate,
            reason: $request->validated('reason'),
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
