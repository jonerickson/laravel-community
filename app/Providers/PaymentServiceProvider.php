<?php

declare(strict_types=1);

namespace App\Providers;

use App\Drivers\Payments\PaymentProcessor;
use App\Managers\PaymentManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

class PaymentServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton('payment-processor', fn (Application $app): PaymentManager => new PaymentManager($app));
        $this->app->bind(PaymentProcessor::class, fn (Application $app) => $app['payment-processor']->driver());
    }
}
