<?php

declare(strict_types=1);

namespace App\Managers;

use App\Contracts\PaymentProcessor;
use App\Drivers\Payments\StripeDriver;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class PaymentManager extends Manager implements PaymentProcessor
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('payment.default', 'stripe');
    }

    public function createProduct(Product $product): Product
    {
        return $this->driver()->createProduct($product);
    }

    public function getProduct(Product $product): Product
    {
        return $this->driver()->getProduct($product);
    }

    public function updateProduct(Product $product): Product
    {
        return $this->driver()->updateProduct($product);
    }

    public function deleteProduct(Product $product): bool
    {
        return $this->driver()->deleteProduct($product);
    }

    public function listProducts(array $filters = []): Collection
    {
        return $this->driver()->listProducts($filters);
    }

    protected function createStripeDriver(): PaymentProcessor
    {
        $stripeSecret = $this->config->get('services.stripe.secret');

        if (blank($stripeSecret)) {
            throw new InvalidArgumentException('Stripe secret is not defined.');
        }

        return new StripeDriver(
            stripeSecret: $stripeSecret,
        );
    }
}
