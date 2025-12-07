<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

use App\Contracts\PaymentProcessor;
use App\Data\CustomerData;
use App\Data\DiscountData;
use App\Data\InvoiceData;
use App\Data\PaymentMethodData;
use App\Data\PriceData;
use App\Data\ProductData;
use App\Data\SubscriptionData;
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

class NullDriver implements PaymentProcessor
{
    public function createProduct(Product $product): ?ProductData
    {
        return null;
    }

    public function getProduct(Product $product): ?ProductData
    {
        return null;
    }

    public function updateProduct(Product $product): ?ProductData
    {
        return null;
    }

    public function deleteProduct(Product $product): bool
    {
        return false;
    }

    public function listProducts(array $filters = []): mixed
    {
        return collect();
    }

    public function createPrice(Price $price): ?PriceData
    {
        return null;
    }

    public function updatePrice(Price $price): ?PriceData
    {
        return null;
    }

    public function changePrice(Price $price): ?PriceData
    {
        return null;
    }

    public function deletePrice(Price $price): bool
    {
        return false;
    }

    public function listPrices(Product $product, array $filters = []): mixed
    {
        return collect();
    }

    public function findInvoice(Order $order): ?InvoiceData
    {
        return null;
    }

    public function createPaymentMethod(User $user, string $paymentMethodId): ?PaymentMethodData
    {
        return null;
    }

    public function listPaymentMethods(User $user): mixed
    {
        return collect();
    }

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): ?PaymentMethodData
    {
        return null;
    }

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool
    {
        return false;
    }

    public function searchCustomer(string $field, string $value): ?CustomerData
    {
        return null;
    }

    public function createCustomer(User $user, bool $force = false): bool
    {
        return false;
    }

    public function getCustomer(User $user): ?CustomerData
    {
        return null;
    }

    public function deleteCustomer(User $user): bool
    {
        return false;
    }

    public function createDiscount(array $options): ?DiscountData
    {
        return null;
    }

    public function startSubscription(Order $order, bool $chargeNow = true, bool $firstParty = true, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete, CarbonInterface|int|null $backdateStartDate = null, CarbonInterface|int|null $billingCycleAnchor = null, ?string $successUrl = null, ?string $cancelUrl = null, array $customerOptions = [], array $subscriptionOptions = []): bool|string|SubscriptionData
    {
        return false;
    }

    public function swapSubscription(User $user, Price $price, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete): bool|SubscriptionData
    {
        return false;
    }

    public function cancelSubscription(User $user, bool $cancelNow = false, ?string $reason = null): bool
    {
        return false;
    }

    public function continueSubscription(User $user): bool
    {
        return false;
    }

    public function updateSubscription(User $user, array $options): ?SubscriptionData
    {
        return null;
    }

    public function currentSubscription(User $user): ?SubscriptionData
    {
        return null;
    }

    public function listSubscriptions(User $user, array $filters = []): mixed
    {
        return collect();
    }

    /**
     * @return Collection<int, CustomerData>
     */
    public function listSubscribers(?Price $price = null): mixed
    {
        return collect();
    }

    public function getCheckoutUrl(Order $order): bool|string
    {
        return false;
    }

    public function processCheckoutSuccess(Request $request, Order $order): bool
    {
        return false;
    }

    public function processCheckoutCancel(Request $request, Order $order): bool
    {
        return false;
    }

    public function refundOrder(Order $order, OrderRefundReason $reason, ?string $notes = null): bool
    {
        return false;
    }

    public function cancelOrder(Order $order): bool
    {
        return false;
    }

    public function syncCustomerInformation(User $user): bool
    {
        return false;
    }

    public function getBillingPortalUrl(User $user): ?string
    {
        return null;
    }
}
