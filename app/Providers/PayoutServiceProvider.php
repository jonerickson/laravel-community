<?php

declare(strict_types=1);

namespace App\Providers;

use App\Drivers\Payouts\PayoutProcessor;
use App\Managers\PayoutManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

class PayoutServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton('payout-processor', fn (Application $app): PayoutManager => new PayoutManager($app));
        $this->app->bind(PayoutProcessor::class, fn (Application $app) => $app['payout-processor']->driver());
    }
}
