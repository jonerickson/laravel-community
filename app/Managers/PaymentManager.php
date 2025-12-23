<?php

declare(strict_types=1);

namespace App\Managers;

use App\Data\CustomerData;
use App\Data\DiscountData;
use App\Data\InvoiceData;
use App\Data\PaymentMethodData;
use App\Data\PriceData;
use App\Data\ProductData;
use App\Data\SubscriptionData;
use App\Drivers\Payments\NullDriver;
use App\Drivers\Payments\PaymentProcessor;
use App\Drivers\Payments\StripeDriver;
use App\Enums\OrderRefundReason;
use App\Enums\PaymentBehavior;
use App\Enums\ProrationBehavior;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function listProducts(array $filters = []): ?Collection
    {
        return $this->driver()->listProducts($filters);
    }

    public function findInvoice(string $invoiceId, array $params = []): ?InvoiceData
    {
        return $this->driver()->findInvoice($invoiceId, $params);
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

    public function listPrices(Product $product, array $filters = []): ?Collection
    {
        return $this->driver()->listPrices($product, $filters);
    }

    public function createPaymentMethod(User $user, string $paymentMethodId): ?PaymentMethodData
    {
        return $this->driver()->createPaymentMethod($user, $paymentMethodId);
    }

    public function listPaymentMethods(User $user): ?Collection
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

    public function createCoupon(Discount $discount): ?DiscountData
    {
        return $this->driver()->createCoupon($discount);
    }

    public function startSubscription(Order $order, bool $chargeNow = true, bool $firstParty = true, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete, CarbonInterface|int|null $backdateStartDate = null, CarbonInterface|int|null $billingCycleAnchor = null, ?string $successUrl = null, ?string $cancelUrl = null, array $customerOptions = [], array $subscriptionOptions = []): bool|string|SubscriptionData
    {
        return $this->driver()->startSubscription($order, $chargeNow, $firstParty, $prorationBehavior, $paymentBehavior, $backdateStartDate, $billingCycleAnchor, $successUrl, $cancelUrl, $customerOptions, $subscriptionOptions);
    }

    public function swapSubscription(User $user, Price $price, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete, array $options = []): bool|SubscriptionData
    {
        return $this->driver()->swapSubscription($user, $price, $prorationBehavior, $paymentBehavior);
    }

    public function cancelSubscription(User $user, bool $cancelNow = false, ?string $reason = null): bool
    {
        return $this->driver()->cancelSubscription($user, $cancelNow, $reason);
    }

    public function continueSubscription(User $user): bool
    {
        return $this->driver()->continueSubscription($user);
    }

    public function updateSubscription(User $user, array $options): ?SubscriptionData
    {
        return $this->driver()->updateSubscription($user, $options);
    }

    public function currentSubscription(User $user): ?SubscriptionData
    {
        return $this->driver()->currentSubscription($user);
    }

    public function listSubscriptions(?User $user = null, array $filters = []): Collection
    {
        return $this->driver()->listSubscriptions($user, $filters);
    }

    public function listSubscribers(?Price $price = null): ?Collection
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
