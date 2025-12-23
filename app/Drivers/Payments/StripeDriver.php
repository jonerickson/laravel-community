<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Data\CustomerData;
use App\Data\DiscountData;
use App\Data\InvoiceData;
use App\Data\PaymentMethodData;
use App\Data\PriceData;
use App\Data\ProductData;
use App\Data\SubscriptionData;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\OrderRefundReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentBehavior;
use App\Enums\PriceType;
use App\Enums\ProductType;
use App\Enums\ProrationBehavior;
use App\Enums\SubscriptionInterval;
use App\Jobs\Stripe\UpdateCustomerInformation;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\Subscription as SubscriptionModel;
use App\Models\User;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Laravel\Cashier\PaymentMethod;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionBuilder;
use Stripe\Checkout\Session;
use Stripe\Coupon;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\RateLimitException;
use Stripe\Invoice;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeDriver implements PaymentProcessor
{
    protected StripeClient $stripe;

    public function __construct(private readonly string $stripeSecret)
    {
        Stripe::setApiKey($this->stripeSecret);
        $this->stripe = new StripeClient($this->stripeSecret);
    }

    public function createProduct(Product $product): ?ProductData
    {
        return $this->executeWithErrorHandling('createProduct', function () use ($product): ProductData {
            $payload = [
                'name' => $product->name,
                'tax_code' => $product->tax_code?->getStripeCode(),
                'metadata' => Arr::dot([
                    'product_id' => $product->reference_id,
                    ...$product->metadata ?? [],
                ]),
                'active' => true,
            ];

            $description = Str::of($product->description)->stripTags()->limit()->toString();

            if (filled($description)) {
                $payload['description'] = $description;
            }

            $stripeProduct = $this->stripe->products->create($payload);

            $product->updateQuietly([
                'external_product_id' => $stripeProduct->id,
            ]);

            return ProductData::from($product);
        });
    }

    public function getProduct(Product $product): ?ProductData
    {
        return $this->executeWithErrorHandling('getProduct', function () use ($product): ProductData {
            $this->stripe->products->retrieve($product->external_product_id);

            return ProductData::from($product);
        });
    }

    public function updateProduct(Product $product): ?ProductData
    {
        return $this->executeWithErrorHandling('updateProduct', function () use ($product): ProductData {
            $payload = [
                'name' => $product->name,
                'default_price' => $product->prices()->latest()->get()->firstWhere('is_default', true)->external_price_id,
                'metadata' => Arr::dot([
                    'product_id' => $product->reference_id,
                    ...$product->metadata ?? [],
                ]),
            ];

            $description = Str::of($product->description)->stripTags()->limit()->toString();

            if (filled($description)) {
                $payload['description'] = $description;
            }

            $this->stripe->products->update($product->external_product_id, $payload);

            return ProductData::from($product);
        });
    }

    public function deleteProduct(Product $product): bool
    {
        return $this->executeWithErrorHandling('deleteProduct', function () use ($product): bool {
            if (! $product->external_product_id) {
                return false;
            }

            $this->stripe->products->delete($product->external_product_id);

            $product->updateQuietly([
                'external_product_id' => null,
            ]);

            return true;
        }, false);
    }

    public function listProducts(array $filters = []): ?Collection
    {
        return $this->executeWithErrorHandling('listProducts', function () use ($filters): Collection {
            $query = Product::query()->whereNotNull('external_product_id');

            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            return ProductData::collect($query->get());
        });
    }

    public function createPrice(Price $price): ?PriceData
    {
        return $this->executeWithErrorHandling('createPrice', function () use ($price): PriceData {
            if (! $price->product->external_product_id) {
                throw new Exception('Product must have an external price ID to update.');
            }

            $stripeParams = [
                'product' => $price->product->external_product_id,
                'unit_amount' => $price->amount * 100,
                'currency' => strtolower($price->currency),
                'metadata' => Arr::dot([
                    'product_id' => $price->product->reference_id,
                    'price_id' => $price->reference_id,
                    ...$price->metadata ?? [],
                ]),
            ];

            if ($price->type === PriceType::Recurring && filled($price->interval)) {
                $stripeParams['recurring'] = [
                    'interval' => $price->interval->value,
                    'interval_count' => $price->interval_count,
                    'usage_type' => 'licensed',
                ];
            }

            $stripePrice = $this->stripe->prices->create($stripeParams);

            $price->updateQuietly([
                'external_price_id' => $stripePrice->id,
            ]);

            if ($price->is_default) {
                $this->updateProduct($price->product);
            }

            return PriceData::from($price);
        });
    }

    public function updatePrice(Price $price): ?PriceData
    {
        return $this->executeWithErrorHandling('updatePrice', function () use ($price): ?PriceData {
            if (! $price->external_price_id) {
                return null;
            }

            $this->stripe->prices->update($price->external_price_id, [
                'metadata' => Arr::dot([
                    'product_id' => $price->product->reference_id,
                    'price_id' => $price->reference_id,
                    ...$price->metadata ?? [],
                ]),
            ]);

            if ($price->is_default) {
                $this->updateProduct($price->product);
            }

            return PriceData::from($price);
        });
    }

    public function changePrice(Price $price): ?PriceData
    {
        return $this->executeWithErrorHandling('changePrice', function () use ($price): ?PriceData {
            if ($price->external_price_id) {
                $this->deletePrice($price);
            }

            return $this->createPrice($price);
        });
    }

    public function deletePrice(Price $price): bool
    {
        return $this->executeWithErrorHandling('deletePrice', function () use ($price): bool {
            if (! $price->external_price_id) {
                return false;
            }

            $this->stripe->prices->update($price->external_price_id, [
                'active' => false,
            ]);

            $price->update([
                'is_active' => false,
            ]);

            return true;
        }, false);
    }

    public function listPrices(Product $product, array $filters = []): ?Collection
    {
        return $this->executeWithErrorHandling('listPrices', function () use ($product, $filters): Collection {
            if (! $product->external_product_id) {
                throw new Exception('Product must have an external product ID to list prices.');
            }

            $stripeParams = [
                'product' => $product->external_product_id,
                'limit' => $filters['limit'] ?? 100,
            ];

            if (isset($filters['active'])) {
                $stripeParams['active'] = $filters['active'];
            }

            if (isset($filters['currency'])) {
                $stripeParams['currency'] = $filters['currency'];
            }

            if (isset($filters['type'])) {
                $stripeParams['type'] = $filters['type'];
            }

            $stripeProduct = $this->stripe->products->retrieve($product->external_product_id);
            $stripePrices = $this->stripe->prices->all($stripeParams);

            $externalPriceIds = collect($stripePrices->data)->pluck('id')->toArray();
            $localPrices = Price::query()
                ->where('product_id', $product->id)
                ->whereIn('external_price_id', $externalPriceIds)
                ->get()
                ->keyBy('external_price_id');

            $prices = collect($stripePrices->data)->map(function ($stripePrice) use ($localPrices, $product, $stripeProduct) {
                if ($localPrices->has($stripePrice->id)) {
                    return $localPrices->get($stripePrice->id);
                }

                $productPrice = new Price;
                $productPrice->id = null;
                $productPrice->product_id = $product->id;
                $productPrice->external_price_id = $stripePrice->id;
                $productPrice->name = $stripePrice->nickname ?? 'Unnamed Price';
                $productPrice->type = $stripePrice->type ? PriceType::tryFrom($stripePrice->type) : null;
                $productPrice->amount = $stripePrice->unit_amount / 100;
                $productPrice->currency = strtoupper($stripePrice->currency);
                $productPrice->interval = $stripePrice->recurring ? SubscriptionInterval::tryFrom($stripePrice->recurring->interval) : null;
                $productPrice->interval_count = $stripePrice->recurrin ? $stripePrice->recurring->interval_count : 1;
                $productPrice->is_active = $stripePrice->active;
                $productPrice->is_default = $stripePrice->id === $stripeProduct?->default_price;
                $productPrice->metadata = $stripePrice->metadata->toArray();

                return $productPrice;
            });

            return new Collection($prices->all());
        });
    }

    public function findInvoice(string $invoiceId, array $params = []): ?InvoiceData
    {
        return $this->executeWithErrorHandling('findInvoice', function () use ($invoiceId, $params): ?InvoiceData {
            $invoice = $this->stripe->invoices->retrieve($invoiceId, $params);

            if (! $invoice instanceof Invoice) {
                return null;
            }

            return InvoiceData::from($invoice);
        });
    }

    public function createPaymentMethod(User $user, string $paymentMethodId): ?PaymentMethodData
    {
        return $this->executeWithErrorHandling('createPaymentMethod', fn (): PaymentMethodData => PaymentMethodData::from($user->addPaymentMethod($paymentMethodId)));
    }

    public function listPaymentMethods(User $user): ?Collection
    {
        return $this->executeWithErrorHandling('listPaymentMethods', fn (): Collection => PaymentMethodData::collect($user->paymentMethods()));
    }

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): ?PaymentMethodData
    {
        return $this->executeWithErrorHandling('updatePaymentMethod', function () use ($user, $paymentMethodId, $isDefault): ?PaymentMethodData {
            if (! ($paymentMethod = $user->findPaymentMethod($paymentMethodId)) instanceof PaymentMethod) {
                return null;
            }

            if ($isDefault) {
                $user->updateDefaultPaymentMethod($paymentMethodId);
            }

            return PaymentMethodData::from($paymentMethod);
        });
    }

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool
    {
        return $this->executeWithErrorHandling('deletePaymentMethod', function () use ($user, $paymentMethodId): bool {
            if (! $user->findPaymentMethod($paymentMethodId) instanceof PaymentMethod) {
                return false;
            }

            $user->deletePaymentMethod($paymentMethodId);

            return true;
        }, false);
    }

    public function searchCustomer(string $field, string $value): ?CustomerData
    {
        return $this->executeWithErrorHandling('searchCustomer', function () use ($field, $value): ?CustomerData {
            $customers = $this->stripe->customers->search([
                'query' => sprintf('%s:"%s"', $field, $value),
            ]);

            if ($customers->isEmpty()) {
                return null;
            }

            $customer = $customers->first();

            if ($customer->isDeleted()) {
                return null;
            }

            return CustomerData::from($customer);
        });
    }

    public function createCustomer(User $user, bool $force = false): bool
    {
        return $this->executeWithErrorHandling('createCustomer', function () use ($user, $force): bool {
            if ($user->hasStripeId() && ! $force) {
                return true;
            }

            if ($user->hasStripeId() && $force) {
                $user->updateQuietly([
                    'stripe_id' => null,
                ]);
            }

            $user->createAsStripeCustomer();

            return true;
        }, false);
    }

    public function getCustomer(User $user): ?CustomerData
    {
        return $this->executeWithErrorHandling('getCustomer', function () use ($user): ?CustomerData {
            if (! $user->hasStripeId()) {
                return null;
            }

            $customer = $this->stripe->customers->retrieve($user->stripeId());

            if ($customer->isDeleted()) {
                return null;
            }

            return CustomerData::from($customer);
        });
    }

    public function deleteCustomer(User $user): bool
    {
        return $this->executeWithErrorHandling('deleteCustomer', function () use ($user): bool {
            if (! $user->hasStripeId()) {
                return false;
            }

            $this->stripe->customers->delete($user->stripeId());

            $user->forceFill([
                'stripe_id' => null,
            ])->save();

            return true;
        }, false);
    }

    public function createCoupon(Discount $discount): ?DiscountData
    {
        return $this->executeWithErrorHandling('createCoupon', function () use ($discount): ?DiscountData {
            $options = [
                'name' => $discount->code,
                'metadata' => [
                    'discount_id' => $discount->reference_id,
                ],
            ];

            if ($discount->discount_type === DiscountValueType::Fixed) {
                $options['currency'] = 'usd';
                $options['amount_off'] = $discount->pivot
                    ? $discount->pivot->getRawOriginal('amount_applied')
                    : $discount->value;
            }

            if ($discount->discount_type === DiscountValueType::Percentage) {
                $options['percent_off'] = $discount->value;
            }

            if ($discount->type === DiscountType::Cancellation) {
                $options['duration'] = 'once';
            }

            if ($discount->max_uses > 0) {
                $options['max_redemptions'] = $discount->max_uses;
            }

            if (! is_null($discount->expires_at)) {
                $options['redeem_by'] = $discount->expires_at->getTimestamp();
            }

            $coupon = $this->stripe->coupons->create($options);

            if (! $coupon instanceof Coupon) {
                return null;
            }

            return DiscountData::from($coupon);
        });
    }

    public function startSubscription(
        Order $order,
        bool $chargeNow = true,
        bool $firstParty = true,
        ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations,
        PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete,
        CarbonInterface|int|null $backdateStartDate = null,
        CarbonInterface|int|null $billingCycleAnchor = null,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
        array $customerOptions = [],
        array $subscriptionOptions = [],
    ): bool|string|SubscriptionData {
        return $this->executeWithErrorHandling('startSubscription', function () use ($order, $chargeNow, $firstParty, $prorationBehavior, $paymentBehavior, $backdateStartDate, $billingCycleAnchor, $successUrl, $cancelUrl, $customerOptions, $subscriptionOptions): bool|string|SubscriptionData {
            $lineItems = [];

            foreach ($order->items as $orderItem) {
                if (! $priceId = $orderItem->price->external_price_id) {
                    continue;
                }

                $lineItems[] = $priceId;
            }

            if (blank($lineItems)) {
                return false;
            }

            /** @var ?OrderItem $allowPromotionCodes */
            $allowPromotionCodes = $order->items()
                ->with('price.product')
                ->get()
                ->firstWhere('price.product.allow_promotion_codes', true);

            /** @var ?OrderItem $trialDays */
            $trialDays = $order->items()
                ->with('price.product')
                ->get()
                ->firstWhere('price.product.trial_days', '>', 0);

            $metadata = array_merge([
                'order_id' => $order->reference_id,
                'discount_ids' => $order->discounts->pluck('reference_id')->implode(', '),
            ], ...$order->items->map(fn (OrderItem $orderItem) => data_get($orderItem->price->product->metadata, 'metadata', []))->toArray());

            /** @var Subscription|Session $result */
            $result = $order->user
                ->newSubscription('default', $lineItems)
                ->when(filled($trialDays), fn (SubscriptionBuilder $builder) => $builder->trialDays($trialDays->price->product->trial_days))
                ->when(filled($allowPromotionCodes), fn (SubscriptionBuilder $builder) => $builder->allowPromotionCodes())
                ->when(filled($billingCycleAnchor), fn (SubscriptionBuilder $builder) => $builder->trialUntil($billingCycleAnchor))
                ->when($prorationBehavior === ProrationBehavior::None, fn (SubscriptionBuilder $builder) => $builder->noProrate())
                ->when($prorationBehavior === ProrationBehavior::AlwaysInvoice, fn (SubscriptionBuilder $builder) => $builder->alwaysInvoice())
                ->when($prorationBehavior === ProrationBehavior::CreateProrations, fn (SubscriptionBuilder $builder) => $builder->prorate())
                ->when($paymentBehavior === PaymentBehavior::DefaultIncomplete, fn (SubscriptionBuilder $builder) => $builder->defaultIncomplete())
                ->when($paymentBehavior === PaymentBehavior::AllowIncomplete, fn (SubscriptionBuilder $builder) => $builder->allowPaymentFailures())
                ->when($paymentBehavior === PaymentBehavior::PendingIfIncomplete, fn (SubscriptionBuilder $builder) => $builder->pendingIfPaymentFails())
                ->withMetadata($metadata)
                ->when(! $chargeNow, fn (SubscriptionBuilder $builder) => $builder->createAndSendInvoice($customerOptions, $subscriptionOptions))
                ->when(! $firstParty, fn (SubscriptionBuilder $builder): Subscription => $builder->create(
                    customerOptions: $customerOptions,
                    subscriptionOptions: array_filter(array_merge($subscriptionOptions, [
                        'backdate_start_date' => $backdateStartDate instanceof CarbonInterface ? $backdateStartDate->getTimestamp() : null,
                    ]))
                ))
                ->when($firstParty, fn (SubscriptionBuilder $builder) => $builder->checkout(
                    sessionOptions: [
                        'branding_settings' => [
                            'display_name' => config('app.name'),
                            'border_style' => 'rounded',
                            'background_color' => '#f9f9f9',
                            'button_color' => '#171719',
                            'font_family' => 'inter',
                        ],
                        'billing_address_collection' => 'required',
                        'client_reference_id' => $order->reference_id,
                        'consent_collection' => [
                            'terms_of_service' => 'required',
                        ],
                        'custom_text' => [
                            'terms_of_service_acceptance' => [
                                'message' => 'I accept the Terms of Service outlined by '.config('app.name'),
                            ],
                            'submit' => [
                                'message' => 'Order Number: '.$order->reference_id,
                            ],
                        ],
                        'customer_update' => [
                            'name' => 'auto',
                            'address' => 'auto',
                        ],
                        'origin_context' => 'web',
                        'success_url' => URL::signedRoute('store.checkout.success', [
                            'order' => $order->reference_id,
                            'redirect' => $successUrl ?? route('store.subscriptions', ['complete' => 'true']),
                        ]),
                        'cancel_url' => URL::signedRoute('store.checkout.cancel', [
                            'order' => $order->reference_id,
                            'redirect' => $cancelUrl ?? route('store.subscriptions'),
                        ]),
                    ],
                    customerOptions: $customerOptions
                )->asStripeCheckoutSession());

            if ($result instanceof Session) {
                if ($result->status !== Session::STATUS_OPEN) {
                    return false;
                }

                $order->updateQuietly([
                    'external_checkout_id' => $result->id,
                ]);

                return $result->url;
            }

            return SubscriptionData::from($result);
        }, false);
    }

    public function swapSubscription(User $user, Price $price, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete, array $options = []): bool|SubscriptionData
    {
        return $this->executeWithErrorHandling('swapSubscription', function () use ($user, $price, $prorationBehavior, $paymentBehavior, $options): bool {
            if (! $price->external_price_id) {
                return false;
            }

            if ((! $subscription = $user->subscription()) || ! $subscription->valid()) {
                return false;
            }

            $subscription = match ($prorationBehavior) {
                ProrationBehavior::CreateProrations => $subscription->prorate(),
                ProrationBehavior::AlwaysInvoice => $subscription->alwaysInvoice(),
                ProrationBehavior::None => $subscription->noProrate()
            };

            $subscription = match ($paymentBehavior) {
                PaymentBehavior::DefaultIncomplete => $subscription->defaultIncomplete(),
                PaymentBehavior::AllowIncomplete => $subscription->allowPaymentFailures(),
                PaymentBehavior::ErrorIfIncomplete => $subscription->errorIfPaymentFails(),
                PaymentBehavior::PendingIfIncomplete => $subscription->pendingIfPaymentFails(),
            };

            if ($subscription->onTrial()) {
                $subscription->endTrial();
            }

            $pastDueInvoiceId = null;
            if ($wasPastDue = $subscription->pastDue()) {
                $subscription->noProrate()->errorIfPaymentFails();
                $options['billing_cycle_anchor'] = 'now';

                $stripeSubscription = $subscription->asStripeSubscription(
                    expand: ['latest_invoice']
                );

                if ($stripeSubscription->latest_invoice?->status === Invoice::STATUS_OPEN) {
                    $pastDueInvoiceId = $stripeSubscription->latest_invoice->id;
                }
            }

            $updatedSubscription = $subscription->swap($price->external_price_id, $options);

            if (! $updatedSubscription->pastDue() && $wasPastDue && ! is_null($pastDueInvoiceId)) {
                $this->stripe->invoices->voidInvoice($pastDueInvoiceId);
            }

            return true;
        }, false);
    }

    public function cancelSubscription(User $user, bool $cancelNow = false, ?string $reason = null): bool
    {
        return $this->executeWithErrorHandling('cancelSubscription', function () use ($user, $cancelNow, $reason): bool {
            $subscription = $user->subscription();

            if (blank($subscription)) {
                return false;
            }

            if (! $subscription->valid()) {
                return false;
            }

            if ($cancelNow) {
                $subscription->cancelNow();
            } else {
                $subscription->cancel();
            }

            if (! is_null($reason)) {
                $subscription->forceFill([
                    'cancellation_reason' => $reason,
                ])->save();
            }

            return true;
        }, false);
    }

    public function continueSubscription(User $user): bool
    {
        return $this->executeWithErrorHandling('continueSubscription', function () use ($user): bool {
            $subscription = $user->subscription();

            if (blank($subscription)) {
                return false;
            }

            if ($subscription->canceled() && $subscription->onGracePeriod()) {
                $subscription->resume();

                return true;
            }

            return true;
        }, false);
    }

    public function updateSubscription(User $user, array $options): ?SubscriptionData
    {
        return $this->executeWithErrorHandling('updateSubscription', function () use ($user, $options): ?SubscriptionData {
            $subscription = $user->subscription();

            if (blank($subscription)) {
                return null;
            }

            $result = $subscription->updateStripeSubscription($options);

            if (! $result) {
                return null;
            }

            return $this->currentSubscription($user);
        });
    }

    public function currentSubscription(User $user): ?SubscriptionData
    {
        return $this->executeWithErrorHandling('currentSubscription', function () use ($user): ?SubscriptionData {
            if (! ($subscription = $user->subscription()) instanceof Subscription) {
                return null;
            }

            if (! $subscription->active()) {
                return null;
            }

            return SubscriptionData::from($subscription);
        });
    }

    public function listSubscriptions(?User $user = null, array $filters = []): ?Collection
    {
        return $this->executeWithErrorHandling('listSubscriptions', function () use ($user, $filters): Collection {
            $subscriptions = $user instanceof User ? $user->subscriptions() : SubscriptionModel::query()
                ->with('user')
                ->latest();

            if (isset($filters['limit'])) {
                $subscriptions = $subscriptions->limit($filters['limit']);
            }

            if (isset($filters['active']) && $filters['active']) {
                $subscriptions = $subscriptions->active();
            }

            return SubscriptionData::collect($subscriptions->get());
        });
    }

    public function listSubscribers(?Price $price = null): ?Collection
    {
        return $this->executeWithErrorHandling('listSubscribers', fn (): mixed => User::whereHas('subscriptions')
            ->when($price && filled($price->external_price_id), fn (Builder $query) => $query->whereRelation('subscriptions', 'stripe_price', '=', $price->external_price_id))
            ->get()
            ->map(fn (User $user): CustomerData => CustomerData::from($user)));
    }

    public function getCheckoutUrl(Order $order): bool|string
    {
        return $this->executeWithErrorHandling('getCheckoutUrl', function () use ($order) {
            $lineItems = [];

            $mode = Session::MODE_PAYMENT;
            foreach ($order->items as $orderItem) {
                if (! $priceId = $orderItem->price?->external_price_id) {
                    continue;
                }

                if ($mode !== Session::MODE_SUBSCRIPTION && $orderItem->price->product->type === ProductType::Subscription) {
                    $mode = Session::MODE_SUBSCRIPTION;
                }

                $lineItems[] = [
                    'price' => $priceId,
                    'quantity' => $orderItem->quantity,
                ];
            }

            if (blank($lineItems)) {
                return false;
            }

            /** @var ?OrderItem $allowPromotionCodes */
            $allowPromotionCodes = $order->items()
                ->with('price.product')
                ->get()
                ->firstWhere('price.product.allow_promotion_codes', true);

            $disallowDiscountCodes = $order->items()
                ->with('price.product')
                ->get()
                ->firstWhere('price.product.allow_discount_codes', false);

            $metadata = array_merge([
                'order_id' => $order->reference_id,
                'discount_ids' => $order->discounts->pluck('reference_id')->implode(', '),
            ], ...$order->items
                ->map(fn (OrderItem $orderItem) => data_get($orderItem->price->product->metadata, 'metadata', []))
                ->toArray());

            $discounts = [];
            if (! $disallowDiscountCodes && $order->discounts->isNotEmpty()) {
                foreach ($order->discounts as $discount) {
                    $coupon = $this->createCoupon($discount);

                    if (! $coupon instanceof DiscountData) {
                        continue;
                    }

                    if (! $externalCouponId = $coupon->externalCouponId) {
                        continue;
                    }

                    $discounts[] = ['coupon' => $externalCouponId];
                }
            }

            $checkoutParams = [
                'client_reference_id' => $order->reference_id,
                'customer' => $order->user->stripeId(),
                'success_url' => URL::signedRoute('store.checkout.success', [
                    'order' => $order->reference_id,
                ]),
                'cancel_url' => URL::signedRoute('store.checkout.cancel', [
                    'order' => $order->reference_id,
                ]),
                'mode' => $mode,
                'line_items' => $lineItems,
                'metadata' => $metadata,
                'consent_collection' => [
                    'terms_of_service' => 'required',
                ],
                'custom_text' => [
                    'terms_of_service_acceptance' => [
                        'message' => 'I accept the Terms of Service outlined by '.config('app.name'),
                    ],
                    'submit' => [
                        'message' => 'Order Number: '.$order->reference_id,
                    ],
                ],
            ];

            if ($mode === Session::MODE_PAYMENT) {
                $checkoutParams['invoice_creation'] = [
                    'enabled' => true,
                    'invoice_data' => [
                        'custom_fields' => [
                            [
                                'name' => 'Order number',
                                'value' => $order->reference_id,
                            ],
                        ],
                        'metadata' => $metadata,
                    ],
                ];

                $checkoutParams['payment_intent_data'] = [
                    'setup_future_usage' => 'off_session',
                    'receipt_email' => $order->user->email,
                    'metadata' => $metadata,
                ];
            }

            if (filled($discounts)) {
                $checkoutParams['discounts'] = $discounts;
            } else {
                $checkoutParams['allow_promotion_codes'] = filled($allowPromotionCodes);
            }

            $checkoutSession = $this->stripe->checkout->sessions->create(array_filter($checkoutParams));

            if ($checkoutSession->status !== Session::STATUS_OPEN) {
                return false;
            }

            $order->updateQuietly([
                'external_checkout_id' => $checkoutSession->id,
            ]);

            return $checkoutSession->url;
        }, false);
    }

    public function processCheckoutSuccess(Request $request, Order $order): bool
    {
        return $this->executeWithErrorHandling('processCheckoutSuccess', function () use ($order): bool {
            if (blank($externalCheckoutId = $order->external_checkout_id)) {
                return false;
            }

            $session = $this->stripe->checkout->sessions->retrieve($externalCheckoutId, [
                'expand' => ['invoice', 'payment_intent.payment_method'],
            ]);

            $order->updateQuietly([
                'status' => OrderStatus::Processing,
                'external_order_id' => $session->payment_intent?->id ?? null,
                'external_payment_id' => $session->payment_intent?->payment_method?->id ?? null,
            ]);

            return true;
        }, false);
    }

    public function processCheckoutCancel(Request $request, Order $order): bool
    {
        return $this->executeWithErrorHandling('processCheckoutCancel', function () use ($order): bool {
            if (blank($order->external_checkout_id)) {
                return false;
            }

            $order->updateQuietly([
                'external_checkout_id' => null,
                'external_payment_id' => null,
                'external_order_id' => null,
                'external_invoice_id' => null,
            ]);

            return true;
        }, false);
    }

    public function refundOrder(Order $order, OrderRefundReason $reason, ?string $notes = null): bool
    {
        return $this->executeWithErrorHandling('refundOrder', function () use ($order, $reason, $notes): bool {
            if (blank($order->external_order_id)) {
                return false;
            }

            if (! $order->status->canRefund()) {
                return false;
            }

            $metadata = [
                'order_id' => $order->reference_id,
                'refund_reason' => $reason->value,
            ];

            if (filled($notes)) {
                $metadata['refund_notes'] = $notes;
            }

            $refund = $this->stripe->refunds->create([
                'payment_intent' => $order->external_order_id,
                'reason' => match ($reason) {
                    OrderRefundReason::Duplicate => 'duplicate',
                    OrderRefundReason::Fraudulent => 'fraudulent',
                    OrderRefundReason::RequestedByCustomer => 'requested_by_customer',
                    default => null,
                },
                'metadata' => $metadata,
            ]);

            $order->update([
                'status' => OrderStatus::Refunded,
                'refund_reason' => $reason,
                'refund_notes' => $notes,
            ]);

            return $refund->status === Refund::STATUS_SUCCEEDED;
        }, false);
    }

    public function cancelOrder(Order $order): bool
    {
        return $this->executeWithErrorHandling('cancelOrder', function () use ($order): bool {
            if (! $order->status->canCancel()) {
                return false;
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
            ]);

            if (filled($order->external_checkout_id)) {
                $session = $this->stripe->checkout->sessions->retrieve($order->external_checkout_id);

                if ($session->status === Session::STATUS_OPEN) {
                    $this->stripe->checkout->sessions->expire($order->external_checkout_id);
                }
            }

            return true;
        }, false);
    }

    public function syncCustomerInformation(User $user): bool
    {
        return $this->executeWithErrorHandling('syncCustomerInformation', function () use ($user): true {
            UpdateCustomerInformation::dispatchIf($user->hasStripeId(), $user);

            return true;
        }, false);
    }

    public function getBillingPortalUrl(User $user): ?string
    {
        if (! $user->hasStripeId()) {
            return null;
        }

        return $this->executeWithErrorHandling('getBillingPortalUrl', fn (): string => $user->billingPortalUrl(
            returnUrl: route('settings.billing'),
        ));
    }

    private function executeWithErrorHandling(string $method, callable $callback, mixed $defaultValue = null): mixed
    {
        if (Http::preventingStrayRequests()) {
            return $defaultValue;
        }

        $attempt = 0;
        $maxRetries = 3;

        while ($attempt < $maxRetries) {
            try {
                return $callback();
            } catch (RateLimitException $exception) {
                $attempt++;
                $retryAfter = (int) ($exception->getHttpHeaders()['Retry-After'] ?? (2 ** $attempt));

                if ($attempt >= $maxRetries) {
                    Log::error('Stripe rate limit exceeded after '.$maxRetries.' attempts in '.$method, [
                        'method' => $method,
                        'exception' => $exception,
                        'retry_after' => $retryAfter,
                    ]);

                    return $defaultValue;
                }

                Log::warning('Stripe rate limit hit in '.$method.', retrying after '.$retryAfter.' seconds', [
                    'method' => $method,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                ]);

                Sleep::for($retryAfter)->seconds();
            } catch (ApiErrorException $exception) {
                Log::error('Stripe payment processor API error in '.$method, ['method' => $method, 'exception' => $exception]);

                return $defaultValue;
            } catch (Exception $exception) {
                Log::error('Stripe payment processor exception in '.$method, ['method' => $method, 'exception' => $exception]);

                return $defaultValue;
            }
        }

        return $defaultValue;
    }
}
