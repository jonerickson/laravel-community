<?php

declare(strict_types=1);

namespace App\Managers;

use App\Contracts\PaymentProcessor;
use App\Data\CustomerData;
use App\Data\InvoiceData;
use App\Data\PaymentMethodData;
use App\Data\PriceData;
use App\Data\ProductData;
use App\Data\SubscriptionData;
use App\Drivers\Payments\NullDriver;
use App\Drivers\Payments\StripeDriver;
use App\Enums\OrderRefundReason;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class PaymentManager extends Manager implements PaymentProcessor
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('payment.default') ?? 'null';
    }

    public function createProduct(Product $product): ?ProductData
    {
        return $this->driver()->createProduct($product);
    }

    public function getProduct(Product $product): ?ProductData
    {
        return $this->driver()->getProduct($product);
    }

    public function updateProduct(Product $product): ?ProductData
    {
        return $this->driver()->updateProduct($product);
    }

    public function deleteProduct(Product $product): bool
    {
        return $this->driver()->deleteProduct($product);
    }

    public function listProducts(array $filters = []): mixed
    {
        return $this->driver()->listProducts($filters);
    }

    public function findInvoice(Order $order): ?InvoiceData
    {
        return $this->driver()->findInvoice($order);
    }

    public function createPrice(Price $price): ?PriceData
    {
        return $this->driver()->createPrice($price);
    }

    public function updatePrice(Price $price): ?PriceData
    {
        return $this->driver()->updatePrice($price);
    }

    public function changePrice(Price $price): ?PriceData
    {
        return $this->driver()->changePrice($price);
    }

    public function deletePrice(Price $price): bool
    {
        return $this->driver()->deletePrice($price);
    }

    public function listPrices(Product $product, array $filters = []): mixed
    {
        return $this->driver()->listPrices($product, $filters);
    }

    public function createPaymentMethod(User $user, string $paymentMethodId): ?PaymentMethodData
    {
        return $this->driver()->createPaymentMethod($user, $paymentMethodId);
    }

    public function listPaymentMethods(User $user): mixed
    {
        return $this->driver()->listPaymentMethods($user);
    }

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): ?PaymentMethodData
    {
        return $this->driver()->updatePaymentMethod($user, $paymentMethodId, $isDefault);
    }

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool
    {
        return $this->driver()->deletePaymentMethod($user, $paymentMethodId);
    }

    public function searchCustomer(string $field, string $value): ?CustomerData
    {
        return $this->driver()->searchCustomer($field, $value);
    }

    public function createCustomer(User $user, bool $force = false): bool
    {
        return $this->driver()->createCustomer($user, $force);
    }

    public function getCustomer(User $user): ?CustomerData
    {
        return $this->driver()->getCustomer($user);
    }

    public function deleteCustomer(User $user): bool
    {
        return $this->driver()->deleteCustomer($user);
    }

    public function startSubscription(Order $order, bool $chargeNow = true, bool $firstParty = true, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete, CarbonInterface|int|null $backdateStartDate = null, CarbonInterface|int|null $billingCycleAnchor = null, ?string $successUrl = null): bool|string|SubscriptionData
    {
        return $this->driver()->startSubscription($order, $chargeNow, $firstParty, $prorationBehavior, $paymentBehavior, $backdateStartDate, $billingCycleAnchor, $successUrl);
    }

    public function swapSubscription(User $user, Price $price, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete): bool|SubscriptionData
    {
        return $this->driver()->swapSubscription($user, $price, $prorationBehavior, $paymentBehavior);
    }

    public function cancelSubscription(User $user, bool $cancelNow = false): bool
    {
        return $this->driver()->cancelSubscription($user, $cancelNow);
    }

    public function continueSubscription(User $user): bool
    {
        return $this->driver()->continueSubscription($user);
    }

    public function currentSubscription(User $user): ?SubscriptionData
    {
        return $this->driver()->currentSubscription($user);
    }

    public function listSubscriptions(?User $user = null, array $filters = []): Collection
    {
        return $this->driver()->listSubscriptions($user, $filters);
    }

    public function listSubscribers(?Price $price = null): mixed
    {
        return $this->driver()->listSubscribers($price);
    }

    public function getCheckoutUrl(Order $order): bool|string
    {
        return $this->driver()->getCheckoutUrl($order);
    }

    public function processCheckoutSuccess(Request $request, Order $order): bool
    {
        return $this->driver()->processCheckoutSuccess($request, $order);
    }

    public function processCheckoutCancel(Request $request, Order $order): bool
    {
        return $this->driver()->processCheckoutCancel($request, $order);
    }

    public function refundOrder(Order $order, OrderRefundReason $reason, ?string $notes = null): bool
    {
        return $this->driver()->refundOrder($order, $reason, $notes);
    }

    public function cancelOrder(Order $order): bool
    {
        return $this->driver()->cancelOrder($order);
    }

    public function syncCustomerInformation(User $user): bool
    {
        return $this->driver()->syncCustomerInformation($user);
    }

    public function getBillingPortalUrl(User $user): ?string
    {
        return $this->driver()->getBillingPortalUrl($user);
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

    protected function createNullDriver(): PaymentProcessor
    {
        return new NullDriver;
    }
}
