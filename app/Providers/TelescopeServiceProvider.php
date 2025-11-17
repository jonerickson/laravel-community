<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Role;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Override;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        Telescope::filter(fn (IncomingEntry $entry): bool => match (true) {
            App::runningConsoleCommand('app:migrate') => false,
            $this->app->environment('local', 'staging') => true,
            $entry->isReportableException(), $entry->isFailedRequest(), $entry->isFailedJob(), $entry->isScheduledTask() => true,
            default => $entry->hasMonitoredTag()
        });
    }

    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local', 'staging')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    #[Override]
    protected function gate(): void
    {
        Gate::define('viewTelescope', fn ($user): bool => $user && $user->hasRole(Role::Administrator));
    }
}
