<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getDefaultDriver()
 * @method static \App\Data\ProductData|null createProduct(\App\Models\Product $product)
 * @method static \App\Data\ProductData|null getProduct(\App\Models\Product $product)
 * @method static \App\Data\ProductData|null updateProduct(\App\Models\Product $product)
 * @method static bool deleteProduct(\App\Models\Product $product)
 * @method static \Illuminate\Support\Collection|null listProducts(array $filters = [])
 * @method static \App\Data\InvoiceData|null findInvoice(string $invoiceId, array $params = [])
 * @method static \App\Data\PriceData|null createPrice(\App\Models\Price $price)
 * @method static \App\Data\PriceData|null updatePrice(\App\Models\Price $price)
 * @method static \App\Data\PriceData|null changePrice(\App\Models\Price $price)
 * @method static bool deletePrice(\App\Models\Price $price)
 * @method static \Illuminate\Support\Collection|null listPrices(\App\Models\Product $product, array $filters = [])
 * @method static \App\Data\PaymentMethodData|null createPaymentMethod(\App\Models\User $user, string $paymentMethodId)
 * @method static \Illuminate\Support\Collection|null listPaymentMethods(\App\Models\User $user)
 * @method static \App\Data\PaymentMethodData|null updatePaymentMethod(\App\Models\User $user, string $paymentMethodId, bool $isDefault)
 * @method static bool deletePaymentMethod(\App\Models\User $user, string $paymentMethodId)
 * @method static \App\Data\CustomerData|null searchCustomer(string $field, string $value)
 * @method static bool createCustomer(\App\Models\User $user, bool $force = false)
 * @method static \App\Data\CustomerData|null getCustomer(\App\Models\User $user)
 * @method static bool deleteCustomer(\App\Models\User $user)
 * @method static \App\Data\DiscountData|null createCoupon(\App\Models\Discount $discount)
 * @method static \App\Data\SubscriptionData|string|bool startSubscription(\App\Models\Order $order, bool $chargeNow = true, bool $firstParty = true, \App\Enums\ProrationBehavior $prorationBehavior = 'create_prorations', \App\Enums\PaymentBehavior $paymentBehavior = 'default_incomplete', \Carbon\CarbonInterface|int|null $backdateStartDate = null, \Carbon\CarbonInterface|int|null $billingCycleAnchor = null, string|null $successUrl = null, string|null $cancelUrl = null, array $customerOptions = [], array $subscriptionOptions = [])
 * @method static \App\Data\SubscriptionData|bool swapSubscription(\App\Models\User $user, \App\Models\Price $price, \App\Enums\ProrationBehavior $prorationBehavior = 'create_prorations', \App\Enums\PaymentBehavior $paymentBehavior = 'default_incomplete', array $options = [])
 * @method static bool cancelSubscription(\App\Models\User $user, bool $cancelNow = false, string|null $reason = null)
 * @method static bool continueSubscription(\App\Models\User $user)
 * @method static \App\Data\SubscriptionData|null updateSubscription(\App\Models\User $user, array $options)
 * @method static \App\Data\SubscriptionData|null currentSubscription(\App\Models\User $user)
 * @method static \Illuminate\Support\Collection listSubscriptions(\App\Models\User|null $user = null, array $filters = [])
 * @method static \Illuminate\Support\Collection|null listSubscribers(\App\Models\Price|null $price = null)
 * @method static string|bool getCheckoutUrl(\App\Models\Order $order)
 * @method static bool processCheckoutSuccess(\Illuminate\Http\Request $request, \App\Models\Order $order)
 * @method static bool processCheckoutCancel(\Illuminate\Http\Request $request, \App\Models\Order $order)
 * @method static bool refundOrder(\App\Models\Order $order, \App\Enums\OrderRefundReason $reason, string|null $notes = null)
 * @method static bool cancelOrder(\App\Models\Order $order)
 * @method static bool syncCustomerInformation(\App\Models\User $user)
 * @method static string|null getBillingPortalUrl(\App\Models\User $user)
 * @method static mixed driver(string|null $driver = null)
 * @method static \App\Managers\PaymentManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \App\Managers\PaymentManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \App\Managers\PaymentManager forgetDrivers()
 *
 * @see \App\Managers\PaymentManager
 */
class PaymentProcessor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'payment-processor';
    }
}
