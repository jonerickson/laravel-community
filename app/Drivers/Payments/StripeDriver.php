<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use AllowDynamicProperties;
use App\Contracts\PaymentProcessor;
use App\Models\Product;
use App\Models\ProductPrice;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
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

        return true;
    }

    private function getIdempotencyKey(): string
    {
        $callingMethod = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown';
        $requestId = Context::get('request_id');

        return $requestId ? "$requestId-$callingMethod" : $callingMethod;
    }
}
