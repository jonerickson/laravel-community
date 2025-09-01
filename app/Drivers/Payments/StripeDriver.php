<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Contracts\PaymentProcessor;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
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
            'description' => $product->description,
            'metadata' => [
                'laravel_product_id' => $product->id,
                ...$product->metadata ?? [],
            ],
            'active' => true,
        ]);

        $product->stripe_product_id = $stripeProduct->id;
        $product->save();

        return $product;
    }

    /**
     * @throws ApiErrorException
     */
    public function getProduct(Product $product): Product
    {
        $this->stripe->products->retrieve($product->stripe_product_id);

        return $product;
    }

    /**
     * @throws ApiErrorException
     */
    public function updateProduct(Product $product): Product
    {
        $this->stripe->products->update($product->stripe_product_id, [
            'name' => $product->name,
            'description' => $product->description,
            'metadata' => [
                'laravel_product_id' => $product->id,
                ...$product->metadata ?? [],
            ],
        ]);

        return $product;
    }

    /**
     * @throws ApiErrorException
     */
    public function deleteProduct(Product $product): bool
    {
        if (! $product->stripe_product_id) {
            return false;
        }

        $this->stripe->products->delete($product->stripe_product_id);
        $product->stripe_product_id = null;
        $product->save();

        return true;
    }

    public function listProducts(array $filters = []): Collection
    {
        $query = Product::query()->whereNotNull('stripe_product_id');

        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->get();
    }
}
