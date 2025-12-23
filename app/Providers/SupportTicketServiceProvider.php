<?php

declare(strict_types=1);

namespace App\Providers;

use App\Drivers\SupportTickets\SupportTicketProvider;
use App\Managers\SupportTicketManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

class SupportTicketServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton('support-ticket', fn (Application $app): SupportTicketManager => new SupportTicketManager($app));
        $this->app->bind(SupportTicketProvider::class, fn (Application $app) => $app['support-ticket']->driver());
    }
}
