<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use App\Events\UserIntegrationDeleted;

class LogSocialConnectionDeleted
{
    public function handle(UserIntegrationDeleted $event): void
    {
        if ($event->social->user && method_exists($event->social->user, 'logSocialDisconnected')) {
            $event->social->user->logSocialDisconnected($event->social->provider);
        }
    }
}
