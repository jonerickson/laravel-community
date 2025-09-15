<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use App\Events\UserSocialDeleted;

class LogSocialConnectionDeleted
{
    public function handle(UserSocialDeleted $event): void
    {
        if ($event->social->user && method_exists($event->social->user, 'logSocialDisconnected')) {
            $event->social->user->logSocialDisconnected($event->social->provider);
        }
    }
}
