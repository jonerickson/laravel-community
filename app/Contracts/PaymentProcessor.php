<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\CustomerData;
use App\Data\InvoiceData;
use App\Data\PaymentMethodData;
use App\Data\PriceData;
use App\Data\ProductData;
use App\Data\SubscriptionData;
use App\Enums\OrderRefundReason;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

interface PaymentProcessor
{
    public function createProduct(Product $product): ?ProductData;

    public function getProduct(Product $product): ?ProductData;

    public function updateProduct(Product $product): ?ProductData;

    public function deleteProduct(Product $product): bool;

    /**
     * @return Collection<int, ProductData>
     */
    public function listProducts(array $filters = []): mixed;

    public function createPrice(Price $price): ?PriceData;

    public function updatePrice(Price $price): ?PriceData;

    public function deletePrice(Price $price): bool;

    /**
     * @return Collection<int, PriceData>
     */
    public function listPrices(Product $product, array $filters = []): mixed;

    public function findInvoice(Order $order): ?InvoiceData;

    public function createPaymentMethod(User $user, string $paymentMethodId): ?PaymentMethodData;

    /**
     * @return Collection<int, PaymentMethodData>
     */
    public function listPaymentMethods(User $user): mixed;

    public function updatePaymentMethod(User $user, string $paymentMethodId, bool $isDefault): ?PaymentMethodData;

    public function deletePaymentMethod(User $user, string $paymentMethodId): bool;

    public function createCustomer(User $user): bool;

    public function getCustomer(User $user): ?CustomerData;

    public function deleteCustomer(User $user): bool;

    public function startSubscription(Order $order, bool $chargeNow = true, bool $firstParty = true, ?string $successUrl = null): bool|string|SubscriptionData;

    public function cancelSubscription(User $user, bool $cancelNow = false): bool;

    public function continueSubscription(User $user): bool;

    public function currentSubscription(User $user): ?SubscriptionData;

    /**
     * @return Collection<int, SubscriptionData>
     */
    public function listSubscriptions(User $user, array $filters = []): mixed;

    public function getCheckoutUrl(Order $order): bool|string;

    public function processCheckoutSuccess(Request $request, Order $order): bool;

    public function processCheckoutCancel(Request $request, Order $order): bool;

    public function refundOrder(Order $order, OrderRefundReason $reason, ?string $notes = null): bool;

    public function cancelOrder(Order $order): bool;

    public function syncCustomerInformation(User $user): bool;
}
