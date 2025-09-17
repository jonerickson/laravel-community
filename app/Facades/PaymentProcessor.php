<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getDefaultDriver()
 * @method static \App\Models\Product createProduct(\App\Models\Product $product)
 * @method static \App\Models\Product getProduct(\App\Models\Product $product)
 * @method static \App\Models\Product updateProduct(\App\Models\Product $product)
 * @method static bool deleteProduct(\App\Models\Product $product)
 * @method static \Illuminate\Database\Eloquent\Collection listProducts(array $filters = [])
 * @method static \App\Models\Price createPrice(\App\Models\Product $product, \App\Models\Price $price)
 * @method static \App\Models\Price updatePrice(\App\Models\Product $product, \App\Models\Price $price)
 * @method static bool deletePrice(\App\Models\Product $product, \App\Models\Price $price)
 * @method static \Illuminate\Database\Eloquent\Collection listPrices(\App\Models\Product $product, array $filters = [])
 * @method static \App\Data\PaymentMethodData createPaymentMethod(\App\Models\User $user, string $paymentMethodId)
 * @method static \Illuminate\Database\Eloquent\Collection getPaymentMethods(\App\Models\User $user)
 * @method static bool updatePaymentMethod(\App\Models\User $user, string $paymentMethodId, bool $isDefault)
 * @method static bool deletePaymentMethod(\App\Models\User $user, string $paymentMethodId)
 * @method static string|bool startSubscription(\App\Models\User $user, \App\Models\Order $order)
 * @method static bool cancelSubscription(\App\Models\User $user, \App\Models\Price $price)
 * @method static bool isSubscribedToProduct(\App\Models\User $user, \App\Models\Product $product)
 * @method static bool isSubscribedToPrice(\App\Models\User $user, \App\Models\Price $price)
 * @method static string|bool redirectToCheckout(\App\Models\User $user, \App\Models\Order $order)
 * @method static bool processCheckoutSuccess(\Illuminate\Http\Request $request, \App\Models\Order $order)
 * @method static bool processCheckoutCancel(\Illuminate\Http\Request $request, \App\Models\Order $order)
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
