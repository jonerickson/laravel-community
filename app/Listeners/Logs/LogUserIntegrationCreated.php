<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use App\Events\UserIntegrationCreated;

class LogUserIntegrationCreated
{
    public function handle(UserIntegrationCreated $event): void
    {
        if ($event->integration->user && method_exists($event->integration->user, 'logIntegrationConnected')) {
            $event->integration->user->logIntegrationConnected($event->integration->provider);
        }
    }
}
