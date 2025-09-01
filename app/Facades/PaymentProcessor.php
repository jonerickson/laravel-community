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
