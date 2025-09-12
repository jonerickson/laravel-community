<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use AllowDynamicProperties;
use App\Contracts\PaymentProcessor;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;

#[AllowDynamicProperties]
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

        $this->clearProductCaches();

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

        $this->clearProductCaches();

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

        $this->clearProductCaches();
        $this->clearPriceCaches($product->id);

        return true;
    }

    public function listProducts(array $filters = []): Collection
    {
        $cacheKey = 'stripe.products.'.md5(serialize($filters));

        return Cache::remember($cacheKey, now()->addHour(), function () use ($filters) {
            $query = Product::query()->whereNotNull('external_product_id');

            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            return $query->get();
        });
    }

    /**
     * @throws ApiErrorException
     * @throws Exception
     */
    public function createPrice(Product $product, ProductPrice $price): ProductPrice
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
                'interval' => $price->interval,
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
    public function updatePrice(Product $product, ProductPrice $price): ProductPrice
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
    public function deletePrice(Product $product, ProductPrice $price): bool
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

        $cacheKey = "stripe.prices.{$product->id}.".md5(serialize($filters));

        return Cache::remember($cacheKey, now()->addMinutes(45), function () use ($product, $filters) {
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
            $localPrices = ProductPrice::query()
                ->where('product_id', $product->id)
                ->whereIn('external_price_id', $externalPriceIds)
                ->get()
                ->keyBy('external_price_id');

            $prices = collect($stripePrices->data)->map(function ($stripePrice) use ($localPrices, $product) {
                if ($localPrices->has($stripePrice->id)) {
                    return $localPrices->get($stripePrice->id);
                }

                $productPrice = new ProductPrice;
                $productPrice->id = null;
                $productPrice->product_id = $product->id;
                $productPrice->external_price_id = $stripePrice->id;
                $productPrice->name = $stripePrice->nickname ?? 'Unnamed Price';
                $productPrice->amount = $stripePrice->unit_amount / 100;
                $productPrice->currency = strtoupper($stripePrice->currency);
                $productPrice->interval = $stripePrice->recurring->interval ?? null;
                $productPrice->interval_count = $stripePrice->recurring->interval_count ?? 1;
                $productPrice->is_active = $stripePrice->active;
                $productPrice->is_default = false;
                $productPrice->metadata = $stripePrice->metadata->toArray();

                return $productPrice;
            });

            return new Collection($prices->all());
        });
    }

    public function getInvoices(User $user, array $filters = []): Collection
    {
        $cacheKey = "stripe.invoices.{$user->id}.".md5(serialize($filters));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user, $filters) {
            $query = $user->invoices(
                parameters: $filters
            );

            if (isset($filters['limit'])) {
                $query = $query->take($filters['limit']);
            }

            $invoices = $query->map(function ($invoice) {
                $stripeInvoice = $invoice->asStripeInvoice();

                return (object) [
                    'id' => $invoice->id,
                    'amount_due' => $stripeInvoice->amount_due / 100,
                    'amount_paid' => $stripeInvoice->amount_paid / 100,
                    'amount_remaining' => $stripeInvoice->amount_remaining / 100,
                    'currency' => strtoupper($stripeInvoice->currency),
                    'status' => $stripeInvoice->status,
                    'customer_id' => $stripeInvoice->customer,
                    'subscription_id' => $stripeInvoice->subscription,
                    'invoice_pdf' => $stripeInvoice->invoice_pdf,
                    'hosted_invoice_url' => $stripeInvoice->hosted_invoice_url,
                    'created' => $stripeInvoice->created,
                    'due_date' => $stripeInvoice->due_date,
                    'paid_at' => $stripeInvoice->status_transitions->paid_at ?? null,
                    'metadata' => $stripeInvoice->metadata->toArray(),
                ];
            });

            return new Collection($invoices->all());
        });
    }

    public function createPaymentMethod(User $user, string $paymentMethodId): object
    {
        $paymentMethod = $user->addPaymentMethod($paymentMethodId);

        $this->clearUserCaches($user->id);

        return (object) [
            'id' => $paymentMethod->id,
            'type' => $paymentMethod->type,
            'brand' => $paymentMethod->card->brand ?? null,
            'last4' => $paymentMethod->card->last4 ?? null,
            'exp_month' => $paymentMethod->card->exp_month ?? null,
            'exp_year' => $paymentMethod->card->exp_year ?? null,
            'holder_name' => $paymentMethod->billing_details->name ?? null,
            'email' => $paymentMethod->billing_details->email ?? null,
            'is_default' => $user->defaultPaymentMethod()?->id === $paymentMethod->id,
        ];
    }

    public function getPaymentMethods(User $user): Collection
    {
        $cacheKey = "stripe.payment_methods.{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($user) {
            $paymentMethods = $user->paymentMethods()->map(function ($paymentMethod) use ($user) {
                return (object) [
                    'id' => $paymentMethod->id,
                    'type' => $paymentMethod->type,
                    'brand' => $paymentMethod->card->brand ?? null,
                    'last4' => $paymentMethod->card->last4 ?? null,
                    'exp_month' => $paymentMethod->card->exp_month ?? null,
                    'exp_year' => $paymentMethod->card->exp_year ?? null,
                    'holder_name' => $paymentMethod->billing_details->name ?? null,
                    'email' => $paymentMethod->billing_details->email ?? null,
                    'is_default' => $user->defaultPaymentMethod()?->id === $paymentMethod->id,
                ];
            });

            return new Collection($paymentMethods->all());
        });
    }

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): bool
    {
        if (! $user->findPaymentMethod($paymentMethodId)) {
            return false;
        }

        if ($isDefault) {
            $user->updateDefaultPaymentMethod($paymentMethodId);
        }

        $this->clearUserCaches($user->id);

        return true;
    }

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool
    {
        if (! $user->findPaymentMethod($paymentMethodId)) {
            return false;
        }

        $user->deletePaymentMethod($paymentMethodId);

        $this->clearUserCaches($user->id);

        return true;
    }

    private function getIdempotencyKey(): string
    {
        $callingMethod = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown';
        $requestId = Context::get('request_id');

        return $requestId ? "$requestId-$callingMethod" : $callingMethod;
    }

    private function clearProductCaches(): void
    {
        Cache::forget('stripe.products.*');
    }

    private function clearPriceCaches(?int $productId = null): void
    {
        if ($productId) {
            Cache::forget("stripe.prices.{$productId}.*");
        } else {
            Cache::forget('stripe.prices.*');
        }
    }

    private function clearUserCaches(int $userId): void
    {
        Cache::forget("stripe.invoices.{$userId}.*");
        Cache::forget("stripe.payment_methods.{$userId}");
    }
}
