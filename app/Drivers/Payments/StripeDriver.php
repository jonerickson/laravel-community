<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Contracts\PaymentProcessor;
use App\Data\PaymentMethodData;
use App\Data\SubscriptionData;
use App\Enums\OrderStatus;
use App\Enums\SubscriptionInterval;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Cashier\SubscriptionBuilder;
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

    public function __call(string $name, array $arguments): mixed
    {
        if (Http::preventingStrayRequests()) {
            return null;
        }

        try {
            return $this->$name(...$arguments);
        } catch (ApiErrorException $exception) {
            Log::error('Stripe payment processor API error', $exception);
        } catch (Exception $exception) {
            Log::error('Stripe payment processor exception', $exception);
        }

        return null;
    }

    /**
     * @throws ApiErrorException
     */
    public function createProduct(Product $product): Product
    {
        $stripeProduct = $this->stripe->products->create([
            'name' => $product->name,
            'description' => Str::limit(strip_tags($product->description)),
            'tax_code' => $product->tax_code->getStripeCode(),
            'metadata' => Arr::dot([
                'laravel_product_id' => $product->id,
                'categories' => $product->categories->implode('name', ', '),
                ...$product->metadata ?? [],
            ]),
            'active' => true,
        ], [
            'idempotency_key' => $this->getIdempotencyKey(),
        ]);

        $product->external_product_id = $stripeProduct->id;
        $product->save();

        return $product;
    }

    /**
     * @throws ApiErrorException
     */
    public function getProduct(Product $product): Product
    {
        $this->stripe->products->retrieve($product->external_product_id);

        return $product;
    }

    /**
     * @throws ApiErrorException
     */
    public function updateProduct(Product $product): Product
    {
        $this->stripe->products->update($product->external_product_id, [
            'name' => $product->name,
            'description' => Str::limit(strip_tags($product->description)),
            'metadata' => Arr::dot([
                'laravel_product_id' => $product->id,
                'categories' => $product->categories->implode('name', ', '),
                ...$product->metadata ?? [],
            ]),
        ], [
            'idempotency_key' => $this->getIdempotencyKey(),
        ]);

        return $product;
    }

    /**
     * @throws ApiErrorException
     */
    public function deleteProduct(Product $product): bool
    {
        if (! $product->external_product_id) {
            return false;
        }

        $this->stripe->products->delete($product->external_product_id, null, [
            'idempotency_key' => $this->getIdempotencyKey(),
        ]);

        $product->external_product_id = null;
        $product->save();

        return true;
    }

    public function listProducts(array $filters = []): Collection
    {
        $query = Product::query()->whereNotNull('external_product_id');

        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->get();
    }

    /**
     * @throws ApiErrorException
     * @throws Exception
     */
    public function createPrice(Product $product, Price $price): Price
    {
        if (! $product->external_product_id) {
            throw new Exception('Product must have an external price ID to update.');
        }

        $stripeParams = [
            'product' => $product->external_product_id,
            'unit_amount' => (int) ($price->amount * 100), // Convert to cents
            'currency' => strtolower($price->currency),
            'metadata' => Arr::dot([
                'laravel_product_id' => $product->id,
                'laravel_price_id' => $price->id,
                ...$price->metadata ?? [],
            ]),
        ];

        if ($price->isRecurring()) {
            $stripeParams['recurring'] = [
                'interval' => $price->interval?->value,
                'interval_count' => $price->interval_count,
            ];
        }

        $stripePrice = $this->stripe->prices->create($stripeParams, [
            'idempotency_key' => $this->getIdempotencyKey(),
        ]);

        $price->external_price_id = $stripePrice->id;
        $price->save();

        $this->clearPriceCaches($product->id);

        return $price;
    }

    /**
     * @throws ApiErrorException
     * @throws Exception
     */
    public function updatePrice(Product $product, Price $price): Price
    {
        if (! $price->external_price_id) {
            throw new Exception('Product must have an external price ID to update.');
        }

        $this->stripe->prices->update($price->external_price_id, [
            'metadata' => Arr::dot([
                'laravel_product_id' => $product->id,
                'laravel_price_id' => $price->id,
                ...$price->metadata ?? [],
            ]),
        ], [
            'idempotency_key' => $this->getIdempotencyKey(),
        ]);

        $this->clearPriceCaches($product->id);

        return $price;
    }

    /**
     * @throws ApiErrorException
     */
    public function deletePrice(Product $product, Price $price): bool
    {
        if (! $price->external_price_id) {
            return false;
        }

        $this->stripe->prices->update($price->external_price_id, [
            'active' => false,
        ], [
            'idempotency_key' => $this->getIdempotencyKey(),
        ]);

        $price->external_price_id = null;
        $price->save();

        $this->clearPriceCaches($product->id);

        return true;
    }

    /**
     * @throws ApiErrorException
     * @throws Exception
     */
    public function listPrices(Product $product, array $filters = []): Collection
    {
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

        $stripePrices = $this->stripe->prices->all($stripeParams);

        $externalPriceIds = collect($stripePrices->data)->pluck('id')->toArray();
        $localPrices = Price::query()
            ->where('product_id', $product->id)
            ->whereIn('external_price_id', $externalPriceIds)
            ->get()
            ->keyBy('external_price_id');

        $prices = collect($stripePrices->data)->map(function ($stripePrice) use ($localPrices, $product) {
            if ($localPrices->has($stripePrice->id)) {
                return $localPrices->get($stripePrice->id);
            }

            $productPrice = new Price;
            $productPrice->id = null;
            $productPrice->product_id = $product->id;
            $productPrice->external_price_id = $stripePrice->id;
            $productPrice->name = $stripePrice->nickname ?? 'Unnamed Price';
            $productPrice->amount = $stripePrice->unit_amount / 100;
            $productPrice->currency = strtoupper($stripePrice->currency);
            $productPrice->interval = $stripePrice->recurring ? SubscriptionInterval::tryFrom($stripePrice->recurring->interval) : null;
            $productPrice->interval_count = $stripePrice->recurrin ? $stripePrice->recurring->interval_count : 1;
            $productPrice->is_active = $stripePrice->active;
            $productPrice->is_default = false;
            $productPrice->metadata = $stripePrice->metadata->toArray();

            return $productPrice;
        });

        return new Collection($prices->all());
    }

    public function createPaymentMethod(User $user, string $paymentMethodId): PaymentMethodData
    {
        $paymentMethod = $user->addPaymentMethod($paymentMethodId);

        return PaymentMethodData::from([
            'id' => $paymentMethod->id,
            'type' => $paymentMethod->type,
            'brand' => $paymentMethod->card->brand ?? null,
            'last4' => $paymentMethod->card->last4 ?? null,
            'exp_month' => $paymentMethod->card->exp_month ?? null,
            'exp_year' => $paymentMethod->card->exp_year ?? null,
            'holder_name' => $paymentMethod->billing_details->name ?? null,
            'email' => $paymentMethod->billing_details->email ?? null,
            'is_default' => $user->defaultPaymentMethod()?->id === $paymentMethod->id,
        ]);
    }

    public function getPaymentMethods(User $user): Collection
    {
        $paymentMethods = $user->paymentMethods()->map(function ($paymentMethod) use ($user) {
            return PaymentMethodData::from([
                'id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'brand' => $paymentMethod->card->brand ?? null,
                'last4' => $paymentMethod->card->last4 ?? null,
                'exp_month' => $paymentMethod->card->exp_month ?? null,
                'exp_year' => $paymentMethod->card->exp_year ?? null,
                'holder_name' => $paymentMethod->billing_details->name ?? null,
                'holder_email' => $paymentMethod->billing_details->email ?? null,
                'is_default' => $user->defaultPaymentMethod()?->id === $paymentMethod->id,
            ]);
        });

        return new Collection($paymentMethods->all());
    }

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): bool
    {
        if (! $user->findPaymentMethod($paymentMethodId)) {
            return false;
        }

        if ($isDefault) {
            $user->updateDefaultPaymentMethod($paymentMethodId);
        }

        return true;
    }

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool
    {
        if (! $user->findPaymentMethod($paymentMethodId)) {
            return false;
        }

        $user->deletePaymentMethod($paymentMethodId);

        return true;
    }

    /**
     * @throws Exception
     */
    public function startSubscription(User $user, Order $order, bool $chargeNow = true): bool|string
    {
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

        if (($subscription = $user->subscription()) && $subscription->valid()) {
            if ($chargeNow) {
                $subscription->swapAndInvoice(
                    prices: $lineItems,
                );
            } else {
                $subscription->swap(
                    prices: $lineItems,
                );
            }

            return route('store.subscriptions');
        }

        /** @var ?OrderItem $allowPromotionCodes */
        $allowPromotionCodes = $order->items()->with('product')->get()->firstWhere('product.allow_promotion_codes', true);

        /** @var ?OrderItem $trialDays */
        $trialDays = $order->items()->with('product')->get()->firstWhere('product.trial_days', '>', 0);

        $metadata = array_merge_recursive([
            'order_id' => $order->reference_id,
        ], ...$order->items->map(fn (OrderItem $orderItem) => data_get($orderItem->product->metadata, 'metadata', []))->toArray());

        $checkoutSession = $user
            ->newSubscription('default', $lineItems)
            ->when(! $chargeNow, fn (SubscriptionBuilder $builder) => $builder->createAndSendInvoice())
            ->when(filled($trialDays), fn (SubscriptionBuilder $builder) => $builder->trialDays($trialDays->product->trial_days))
            ->when(filled($allowPromotionCodes), fn (SubscriptionBuilder $builder) => $builder->allowPromotionCodes())
            ->withMetadata($metadata)
            ->checkout([
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
                'success_url' => URL::signedRoute('store.checkout.success', [
                    'order' => $order,
                    'redirect' => route('store.subscriptions', absolute: false),
                ]),
                'cancel_url' => URL::signedRoute('store.checkout.cancel', [
                    'order' => $order,
                    'redirect' => route('store.subscriptions', absolute: false),
                ]),
            ])->asStripeCheckoutSession();

        $order->update([
            'external_checkout_id' => $checkoutSession->id,
        ]);

        return $checkoutSession->url;
    }

    public function cancelSubscription(User $user, bool $cancelNow = false): bool
    {
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
    }

    public function continueSubscription(User $user): bool
    {
        $subscription = $user->subscription();

        if (blank($subscription)) {
            return false;
        }

        if ($subscription->canceled() && $subscription->onGracePeriod()) {
            $subscription->resume();

            return true;
        }

        return true;
    }

    public function currentSubscription(User $user): ?SubscriptionData
    {
        if (! $subscription = $user->subscription()) {
            return null;
        }

        if (! $subscription->valid()) {
            return null;
        }

        if (! $price = Price::query()->where('external_price_id', $subscription->stripe_price)->first()) {
            return null;
        }

        $subscriptionData = SubscriptionData::from($price->product);
        $subscriptionData->current = true;
        $subscriptionData->trialEndsAt = $subscription->trial_ends_at?->toImmutable();
        $subscriptionData->endsAt = $subscription->ends_at?->toImmutable();

        return $subscriptionData;
    }

    public function redirectToCheckout(User $user, Order $order): bool|string
    {
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
        $allowPromotionCodes = $order->items()->with('product')->get()->firstWhere('product.allow_promotion_codes', true);

        $metadata = array_merge_recursive([
            'order_id' => $order->reference_id,
        ], ...$order->items->map(fn (OrderItem $orderItem) => data_get($orderItem->product->metadata, 'metadata', []))->toArray());

        $checkoutSession = $user->checkout($lineItems, [
            'client_reference_id' => $order->reference_id,
            'success_url' => URL::signedRoute('store.checkout.success', [
                'order' => $order,
            ]),
            'cancel_url' => URL::signedRoute('store.checkout.cancel', [
                'order' => $order,
            ]),
            'allow_promotion_codes' => filled($allowPromotionCodes),
            'mode' => 'payment',
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
            'metadata' => $metadata,
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
                'receipt_email' => $user->email,
                'metadata' => $metadata,
            ],
        ])->asStripeCheckoutSession();

        $order->update([
            'external_checkout_id' => $checkoutSession->id,
        ]);

        return $checkoutSession->url;
    }

    /**
     * @throws ApiErrorException
     */
    public function processCheckoutSuccess(Request $request, Order $order): bool
    {
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

        $order->update([
            'external_order_id' => $paymentIntentId ?? null,
            'external_payment_id' => $paymentIntent?->payment_method ?? null,
            'external_invoice_id' => $invoiceId ?? null,
            'status' => $paymentIntent ? (OrderStatus::tryFrom($paymentIntent->status) ?? OrderStatus::Processing) : OrderStatus::Processing,
            'amount' => $paymentIntent?->amount ?? null,
            'invoice_url' => $invoice?->hosted_invoice_url ?? null,
            'invoice_number' => $invoice?->number ?? null,
        ]);

        return true;
    }

    public function processCheckoutCancel(Request $request, Order $order): bool
    {
        if (blank($order->external_checkout_id)) {
            return false;
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
        ]);

        return true;
    }

    private function getIdempotencyKey(): string
    {
        $callingMethod = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown';
        $requestId = Context::get('request_id');

        return $requestId ? "$requestId-$callingMethod" : $callingMethod;
    }
}
