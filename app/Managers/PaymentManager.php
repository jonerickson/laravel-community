<?php

declare(strict_types=1);

namespace App\Managers;

use App\Contracts\PaymentProcessor;
use App\Drivers\Payments\StripeDriver;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
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

    public function createPrice(Product $product, ProductPrice $price): ProductPrice
    {
        return $this->driver()->createPrice($product, $price);
    }

    public function updatePrice(Product $product, ProductPrice $price): ProductPrice
    {
        return $this->driver()->updatePrice($product, $price);
    }

    public function deletePrice(Product $product, ProductPrice $price): bool
    {
        return $this->driver()->deletePrice($product, $price);
    }

    public function listPrices(Product $product, array $filters = []): Collection
    {
        return $this->driver()->listPrices($product, $filters);
    }

    public function getInvoices(User $user, array $filters = []): Collection
    {
        return $this->driver()->getInvoices($user, $filters);
    }

    public function createPaymentMethod(User $user, string $paymentMethodId)
    {
        return $this->driver()->createPaymentMethod($user, $paymentMethodId);
    }

    public function getPaymentMethods(User $user): Collection
    {
        return $this->driver()->getPaymentMethods($user);
    }

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): bool
    {
        return $this->driver()->updatePaymentMethod($user, $paymentMethodId, $isDefault);
    }

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool
    {
        return $this->driver()->deletePaymentMethod($user, $paymentMethodId);
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
