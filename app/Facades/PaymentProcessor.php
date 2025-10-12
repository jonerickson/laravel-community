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
 * @method static mixed listProducts(array $filters = [])
 * @method static \App\Data\InvoiceData|null findInvoice(\App\Models\Order $order)
 * @method static \App\Data\PriceData|null createPrice(\App\Models\Price $price)
 * @method static \App\Data\PriceData|null updatePrice(\App\Models\Price $price)
 * @method static bool deletePrice(\App\Models\Price $price)
 * @method static mixed listPrices(\App\Models\Product $product, array $filters = [])
 * @method static \App\Data\PaymentMethodData|null createPaymentMethod(\App\Models\User $user, string $paymentMethodId)
 * @method static mixed listPaymentMethods(\App\Models\User $user)
 * @method static \App\Data\PaymentMethodData|null updatePaymentMethod(\App\Models\User $user, string $paymentMethodId, bool $isDefault)
 * @method static bool deletePaymentMethod(\App\Models\User $user, string $paymentMethodId)
 * @method static bool createCustomer(\App\Models\User $user)
 * @method static \App\Data\CustomerData|null getCustomer(\App\Models\User $user)
 * @method static bool deleteCustomer(\App\Models\User $user)
 * @method static \App\Data\SubscriptionData|string|bool startSubscription(\App\Models\Order $order, bool $chargeNow = true, bool $firstParty = true)
 * @method static bool cancelSubscription(\App\Models\User $user, bool $cancelNow = false)
 * @method static bool continueSubscription(\App\Models\User $user)
 * @method static \App\Data\SubscriptionData|null currentSubscription(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Collection listSubscriptions(\App\Models\User|null $user = null, array $filters = [])
 * @method static string|bool getCheckoutUrl(\App\Models\Order $order)
 * @method static bool processCheckoutSuccess(\Illuminate\Http\Request $request, \App\Models\Order $order)
 * @method static bool processCheckoutCancel(\Illuminate\Http\Request $request, \App\Models\Order $order)
 * @method static bool refundOrder(\App\Models\Order $order, \App\Enums\OrderRefundReason $reason, string|null $notes = null)
 * @method static bool cancelOrder(\App\Models\Order $order)
 * @method static bool syncCustomerInformation(\App\Models\User $user)
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
