<?php

declare(strict_types=1);

namespace App\Drivers\Payments;

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
use App\Models\Discount;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface PaymentProcessor
{
    public function createProduct(Product $product): ?ProductData;

    public function getProduct(Product $product): ?ProductData;

    public function updateProduct(Product $product): ?ProductData;

    public function deleteProduct(Product $product): bool;

    public function listProducts(array $filters = []): ?Collection;

    public function createPrice(Price $price): ?PriceData;

    public function updatePrice(Price $price): ?PriceData;

    public function changePrice(Price $price): ?PriceData;

    public function deletePrice(Price $price): bool;

    public function listPrices(Product $product, array $filters = []): ?Collection;

    public function findInvoice(string $invoiceId, array $params = []): ?InvoiceData;

    public function createPaymentMethod(User $user, string $paymentMethodId): ?PaymentMethodData;

    public function listPaymentMethods(User $user): ?Collection;

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): ?PaymentMethodData;

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool;

    public function searchCustomer(string $field, string $value): ?CustomerData;

    public function createCustomer(User $user, bool $force = false): bool;

    public function getCustomer(User $user): ?CustomerData;

    public function deleteCustomer(User $user): bool;

    public function createCoupon(Discount $discount): ?DiscountData;

    public function startSubscription(Order $order, bool $chargeNow = true, bool $firstParty = true, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete, CarbonInterface|int|null $backdateStartDate = null, CarbonInterface|int|null $billingCycleAnchor = null, ?string $successUrl = null, ?string $cancelUrl = null, array $customerOptions = [], array $subscriptionOptions = []): bool|string|SubscriptionData;

    public function swapSubscription(User $user, Price $price, ProrationBehavior $prorationBehavior = ProrationBehavior::CreateProrations, PaymentBehavior $paymentBehavior = PaymentBehavior::DefaultIncomplete, array $options = []): bool|SubscriptionData;

    public function cancelSubscription(User $user, bool $cancelNow = false, ?string $reason = null): bool;

    public function continueSubscription(User $user): bool;

    public function updateSubscription(User $user, array $options): ?SubscriptionData;

    public function currentSubscription(User $user): ?SubscriptionData;

    public function listSubscriptions(User $user, array $filters = []): ?Collection;

    public function listSubscribers(?Price $price = null): ?Collection;

    public function getCheckoutUrl(Order $order): bool|string;

    public function processCheckoutSuccess(Request $request, Order $order): bool;

    public function processCheckoutCancel(Request $request, Order $order): bool;

    public function refundOrder(Order $order, OrderRefundReason $reason, ?string $notes = null): bool;

    public function cancelOrder(Order $order): bool;

    public function syncCustomerInformation(User $user): bool;

    public function getBillingPortalUrl(User $user): ?string;
}
