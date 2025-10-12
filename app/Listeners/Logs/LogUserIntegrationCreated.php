<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use App\Events\UserIntegrationCreated;

class LogUserIntegrationCreated
{
    public function handle(UserIntegrationCreated $event): void
    {
        if ($event->social->user && method_exists($event->social->user, 'logSocialConnected')) {
            $event->social->user->logSocialConnected($event->social->provider);
        }
    }
}
