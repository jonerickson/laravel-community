<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Contracts\PaymentProcessor;
use App\Data\CustomerData;
use App\Data\InvoiceData;
use App\Data\PaymentMethodData;
use App\Data\PriceData;
use App\Data\ProductData;
use App\Data\SubscriptionData;
use App\Enums\OrderRefundReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentBehavior;
use App\Enums\PriceType;
use App\Enums\ProrationBehavior;
use App\Enums\SubscriptionInterval;
use App\Jobs\Stripe\UpdateCustomerInformation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\Subscription as SubscriptionModel;
use App\Models\User;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionBuilder;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
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
            $stripeProduct = $this->stripe->products->create([
                'name' => $product->name,
                'description' => Str::limit(strip_tags($product->description)),
                'tax_code' => $product->tax_code?->getStripeCode(),
                'metadata' => Arr::dot([
                    'product_id' => $product->reference_id,
                    ...$product->metadata ?? [],
                ]),
                'active' => true,
            ], [
                'idempotency_key' => $this->getIdempotencyKey(),
            ]);

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
            $this->stripe->products->update($product->external_product_id, [
                'name' => $product->name,
                'description' => Str::limit(strip_tags($product->description)),
                'default_price' => $product->prices()->latest()->get()->firstWhere('is_default', true)->external_price_id,
                'metadata' => Arr::dot([
                    'product_id' => $product->reference_id,
                    ...$product->metadata ?? [],
                ]),
            ], [
                'idempotency_key' => $this->getIdempotencyKey(),
            ]);

            return ProductData::from($product);
        });
    }

    public function deleteProduct(Product $product): bool
    {
        return $this->executeWithErrorHandling('deleteProduct', function () use ($product): bool {
            if (! $product->external_product_id) {
                return false;
            }

            $this->stripe->products->delete($product->external_product_id, null, [
                'idempotency_key' => $this->getIdempotencyKey(),
            ]);

            $product->updateQuietly([
                'external_product_id' => null,
            ]);

            return true;
        }, false);
    }

    /**
     * @return Collection<int, ProductData>
     */
    public function listProducts(array $filters = []): mixed
    {
        return $this->executeWithErrorHandling('listProducts', function () use ($filters): array|\Illuminate\Contracts\Pagination\CursorPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Support\Enumerable|\Spatie\LaravelData\CursorPaginatedDataCollection|\Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection {
            $query = Product::query()->whereNotNull('external_product_id');

            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            return ProductData::collect($query->get());
        }, collect());
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
                $stripeParams['type'] = 'recurring';
                $stripeParams['recurring'] = [
                    'interval' => $price->interval->value,
                    'interval_count' => $price->interval_count,
                    'usage_type' => 'licensed',
                ];
            } else {
                $stripeParams['type'] = 'one_time';
            }

            $stripePrice = $this->stripe->prices->create($stripeParams, [
                'idempotency_key' => $this->getIdempotencyKey(),
            ]);

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
            ], [
                'idempotency_key' => $this->getIdempotencyKey(),
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
            ], [
                'idempotency_key' => $this->getIdempotencyKey(),
            ]);

            return true;
        }, false);
    }

    /**
     * @return Collection<int, PriceData>
     */
    public function listPrices(Product $product, array $filters = []): mixed
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
        }, collect());
    }

    public function findInvoice(Order $order): ?InvoiceData
    {
        return $this->executeWithErrorHandling('findInvoice', function () use ($order): ?\App\Data\InvoiceData {
            if (blank($invoiceId = $order->external_invoice_id)) {
                return null;
            }

            $invoice = $this->stripe->invoices->retrieve($invoiceId);

            return InvoiceData::from([
                'id' => $invoice->id,
                'amount' => $invoice->total,
                'invoice_url' => $invoice->hosted_invoice_url,
                'invoice_pdf_url' => $invoice->invoice_pdf,
                'external_payment_id' => $invoice->payment_intent,
            ]);
        });
    }

    public function createPaymentMethod(User $user, string $paymentMethodId): ?PaymentMethodData
    {
        return $this->executeWithErrorHandling('createPaymentMethod', fn (): PaymentMethodData => PaymentMethodData::from($user->addPaymentMethod($paymentMethodId)));
    }

    /**
     * @return Collection<int, PaymentMethodData>
     */
    public function listPaymentMethods(User $user): mixed
    {
        return $this->executeWithErrorHandling('listPaymentMethods', fn (): array|\Illuminate\Contracts\Pagination\CursorPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Support\Enumerable|\Spatie\LaravelData\CursorPaginatedDataCollection|\Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection => PaymentMethodData::collect($user->paymentMethods()), collect());
    }

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): ?PaymentMethodData
    {
        return $this->executeWithErrorHandling('updatePaymentMethod', function () use ($user, $paymentMethodId, $isDefault): ?\App\Data\PaymentMethodData {
            if (! $paymentMethod = $user->findPaymentMethod($paymentMethodId)) {
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
            if (! $user->findPaymentMethod($paymentMethodId)) {
                return false;
            }

            $user->deletePaymentMethod($paymentMethodId);

            return true;
        }, false);
    }

    public function createCustomer(User $user): bool
    {
        return $this->executeWithErrorHandling('createCustomer', function () use ($user): bool {
            if ($user->hasStripeId()) {
                return true;
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

            return CustomerData::from([
                'id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'currency' => $customer->currency,
                'metadata' => $customer->metadata->toArray(),
            ]);
        });
    }

    public function deleteCustomer(User $user): bool
    {
        return $this->executeWithErrorHandling('deleteCustomer', function () use ($user): bool {
            if (! $user->hasStripeId()) {
                return false;
            }

            $this->stripe->customers->delete($user->stripeId(), null, [
                'idempotency_key' => $this->getIdempotencyKey(),
            ]);

            $user->forceFill([
                'stripe_id' => null,
            ])->save();

            return true;
        }, false);
    }

    public function startSubscription(Order $order, bool $chargeNow = true, bool $firstParty = true, DateTimeInterface|int|null $anchorBillingCycle = null, ?string $successUrl = null): bool|string|SubscriptionData
    {
        return $this->executeWithErrorHandling('startSubscription', function () use ($order, $chargeNow, $firstParty, $anchorBillingCycle, $successUrl): bool|string|SubscriptionData {
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

            $metadata = array_merge_recursive([
                'order_id' => $order->reference_id,
            ], ...$order->items->map(fn (OrderItem $orderItem) => data_get($orderItem->price->product->metadata, 'metadata', []))->toArray());

            /** @var Subscription|Session $result */
            $result = $order->user
                ->newSubscription('default', $lineItems)
                ->when(! $chargeNow, fn (SubscriptionBuilder $builder) => $builder->createAndSendInvoice())
                ->when(filled($trialDays), fn (SubscriptionBuilder $builder) => $builder->trialDays($trialDays->product->trial_days))
                ->when(filled($allowPromotionCodes), fn (SubscriptionBuilder $builder) => $builder->allowPromotionCodes())
                ->when(filled($anchorBillingCycle), fn (SubscriptionBuilder $builder) => $builder->anchorBillingCycleOn($anchorBillingCycle))
                ->withMetadata($metadata)
                ->when(! $firstParty, fn (SubscriptionBuilder $builder) => $builder->create())
                ->when($firstParty, fn (SubscriptionBuilder $builder) => $builder->checkout([
                    'client_reference_id' => $order->reference_id,
                    'origin_context' => 'web',
                    'consent_collection' => [
                        'terms_of_service' => 'required',
                    ],
                    'custom_text' => [
                        'terms_of_service_acceptance' => [
                            'message' => 'I accept the Terms of Service outlined by '.config('app.name'),
                        ],
                        'submit' => [
                            'message' => "Order Number: $order->reference_id",
                        ],
                    ],
                    'branding_settings' => [
                        'display_name' => config('app.name'),
                        'border_style' => 'rounded',
                        'background_color' => '#f9f9f9',
                        'button_color' => '#171719',
                        'font_family' => 'inter',
                    ],
                    'success_url' => URL::signedRoute('store.checkout.success', [
                        'order' => $order,
                        'redirect' => $successUrl ?? route('store.subscriptions', absolute: false),
                    ]),
                    'cancel_url' => URL::signedRoute('store.checkout.cancel', [
                        'order' => $order,
                        'redirect' => route('store.subscriptions', absolute: false),
                    ]),
                ])->asStripeCheckoutSession());

            if ($result instanceof Session) {
                $order->updateQuietly([
                    'external_checkout_id' => $result->id,
                ]);

                return $result->url;
            }

            return SubscriptionData::from($result);
        }, false);
    }

    public function swapSubscription(User $user, Price $price, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete): bool|SubscriptionData
    {
        return $this->executeWithErrorHandling('swapSubscription', function () use ($user, $price, $prorationBehavior, $paymentBehavior): bool {
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

            $subscription->swap($price->external_price_id);

            return true;
        }, false);
    }

    public function cancelSubscription(User $user, bool $cancelNow = false): bool
    {
        return $this->executeWithErrorHandling('cancelSubscription', function () use ($user, $cancelNow): bool {
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

    public function currentSubscription(User $user): ?SubscriptionData
    {
        return $this->executeWithErrorHandling('currentSubscription', function () use ($user): ?SubscriptionData {
            if (! $subscription = $user->subscription()) {
                return null;
            }

            if (! $subscription->active()) {
                return null;
            }

            return SubscriptionData::from($subscription);
        });
    }

    /**
     * @return Collection<int, SubscriptionData>
     */
    public function listSubscriptions(?User $user = null, array $filters = []): mixed
    {
        return $this->executeWithErrorHandling('listSubscriptions', function () use ($user, $filters): array|\Illuminate\Contracts\Pagination\CursorPaginator|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Pagination\AbstractCursorPaginator|\Illuminate\Pagination\AbstractPaginator|\Illuminate\Support\Enumerable|\Spatie\LaravelData\CursorPaginatedDataCollection|\Spatie\LaravelData\DataCollection|\Spatie\LaravelData\PaginatedDataCollection {
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
        }, collect());
    }

    /**
     * @return Collection<int, CustomerData>
     */
    public function listSubscribers(?Price $price = null): mixed
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

            foreach ($order->items as $orderItem) {
                if (! $priceId = $orderItem->price->external_price_id) {
                    continue;
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

            $metadata = array_merge_recursive(
                [
                    'order_id' => $order->reference_id,
                ],
                ...$order->items
                    ->map(fn (OrderItem $orderItem) => data_get($orderItem->price->product->metadata, 'metadata', []))
                    ->toArray()
            );

            $discounts = [];
            if (! $disallowDiscountCodes && $order->discounts->isNotEmpty()) {
                foreach ($order->discounts as $discount) {
                    $couponParams = [
                        'name' => $discount->code,
                        'metadata' => [
                            'order_id' => $order->reference_id,
                            'discount_id' => $discount->id,
                        ],
                    ];

                    if ($discount->discount_type->value === 'percentage') {
                        $couponParams['percent_off'] = $discount->value;
                    } else {
                        $couponParams['amount_off'] = $discount->pivot->getRawOriginal('amount_applied');
                        $couponParams['currency'] = 'usd';
                    }

                    $stripeCoupon = $this->stripe->coupons->create($couponParams, [
                        'idempotency_key' => $this->getIdempotencyKey().'-'.$discount->id,
                    ]);

                    $discounts[] = ['coupon' => $stripeCoupon->id];
                }
            }

            $checkoutParams = [
                'client_reference_id' => $order->reference_id,
                'customer' => $order->user->stripeId(),
                'success_url' => URL::signedRoute('store.checkout.success', [
                    'order' => $order,
                ]),
                'cancel_url' => URL::signedRoute('store.checkout.cancel', [
                    'order' => $order,
                ]),
                'mode' => 'payment',
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
                        'message' => "Order Number: {$order->reference_id}",
                    ],
                ],
                'invoice_creation' => [
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
                ],
                'payment_intent_data' => [
                    'setup_future_usage' => 'off_session',
                    'receipt_email' => $order->user->email,
                    'metadata' => $metadata,
                ],
            ];

            if (filled($discounts)) {
                $checkoutParams['discounts'] = $discounts;
            } else {
                $checkoutParams['allow_promotion_codes'] = filled($allowPromotionCodes);
            }

            $checkoutSession = $this->stripe->checkout->sessions->create(
                $checkoutParams,
                ['idempotency_key' => $this->getIdempotencyKey().'-checkout']
            );

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

            $session = $this->stripe->checkout->sessions->retrieve($externalCheckoutId);

            $invoice = null;
            if ($session && $invoiceId = $session->invoice) {
                $invoice = $this->stripe->invoices->retrieve($invoiceId);
            }

            $paymentIntent = null;
            if ($invoice && $paymentIntentId = $invoice->payment_intent) {
                $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
            }

            $order->updateQuietly([
                'external_order_id' => $paymentIntentId ?? null,
                'external_payment_id' => $paymentIntent?->payment_method ?? null,
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
            ], [
                'idempotency_key' => $this->getIdempotencyKey(),
            ]);

            $order->update([
                'status' => OrderStatus::Refunded,
                'refund_reason' => $reason,
                'refund_notes' => $notes,
            ]);

            return $refund->status === 'succeeded';
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
                $session = $this->stripe->checkout->sessions->retrieve($order->external_checkout_id, null, [
                    'idempotency_key' => $this->getIdempotencyKey(),
                ]);

                if ($session->status === 'open') {
                    $this->stripe->checkout->sessions->expire($order->external_checkout_id, null, [
                        'idempotency_key' => $this->getIdempotencyKey(),
                    ]);
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

    private function executeWithErrorHandling(string $method, callable $callback, mixed $defaultValue = null): mixed
    {
        if (Http::preventingStrayRequests()) {
            return $defaultValue;
        }

        try {
            return $callback();
        } catch (ApiErrorException $exception) {
            Log::error("Stripe payment processor API error in {$method}", ['method' => $method, 'exception' => $exception]);

            return $defaultValue;
        } catch (Exception $exception) {
            Log::error("Stripe payment processor exception in {$method}", ['method' => $method, 'exception' => $exception]);

            return $defaultValue;
        }
    }

    private function getIdempotencyKey(): string
    {
        $callingMethod = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown';
        $requestId = Context::get('request_id');

        return $requestId ? "$requestId-$callingMethod" : $callingMethod;
    }
}
