<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use App\Events\UserIntegrationDeleted;

class LogUserIntegrationDeleted
{
    public function handle(UserIntegrationDeleted $event): void
    {
        if ($event->integration->user && method_exists($event->integration->user, 'logIntegrationDisconnected')) {
            $event->integration->user->logIntegrationDisconnected($event->integration->provider);
        }
    }
}
