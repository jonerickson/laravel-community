<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\SupportTicketService;
use App\Managers\SupportTicketManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class SupportTicketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('support-ticket', fn (Application $app): SupportTicketManager => new SupportTicketManager($app));
        $this->app->bind(SupportTicketService::class, fn (Application $app) => $app['support-ticket']->driver());
    }
}
