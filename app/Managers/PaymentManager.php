<?php

declare(strict_types=1);

namespace App\Managers;

use App\Contracts\PaymentProcessor;
use App\Data\PaymentMethodData;
use App\Drivers\Payments\StripeDriver;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
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

    public function createPrice(Product $product, Price $price): Price
    {
        return $this->driver()->createPrice($product, $price);
    }

    public function updatePrice(Product $product, Price $price): Price
    {
        return $this->driver()->updatePrice($product, $price);
    }

    public function deletePrice(Product $product, Price $price): bool
    {
        return $this->driver()->deletePrice($product, $price);
    }

    public function listPrices(Product $product, array $filters = []): Collection
    {
        return $this->driver()->listPrices($product, $filters);
    }

    public function createPaymentMethod(User $user, string $paymentMethodId): PaymentMethodData
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

    public function startSubscription(User $user, Order $order): bool|string
    {
        return $this->driver()->startSubscription($user, $order);
    }

    public function cancelSubscription(User $user, Price $price): bool
    {
        return $this->driver()->cancelSubscription($user, $price);
    }

    public function isSubscribedToProduct(User $user, Product $product): bool
    {
        return $this->driver()->isSubscribedToProduct($user, $product);
    }

    public function isSubscribedToPrice(User $user, Price $price): bool
    {
        return $this->driver()->isSubscribedToPrice($user, $price);
    }

    public function redirectToCheckout(User $user, Order $order): bool|string
    {
        return $this->driver()->redirectToCheckout($user, $order);
    }

    public function processCheckoutSuccess(Request $request, Order $order): bool
    {
        return $this->driver()->processCheckoutSuccess($request, $order);
    }

    public function processCheckoutCancel(Request $request, Order $order): bool
    {
        return $this->driver()->processCheckoutCancel($request, $order);
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
