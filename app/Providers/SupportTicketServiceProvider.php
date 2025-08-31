<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\SupportTicketDriver;
use App\Managers\SupportTicketManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class SupportTicketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('support-tickets', fn (Application $app): SupportTicketManager => new SupportTicketManager($app));

        $this->app->bind(SupportTicketDriver::class, fn (Application $app) => $app['support-tickets']->driver());
    }

    public function boot(): void
    {
        //
    }
}
