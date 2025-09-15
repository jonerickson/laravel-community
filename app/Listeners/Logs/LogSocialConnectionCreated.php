<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use App\Events\UserSocialCreated;

class LogSocialConnectionCreated
{
    public function handle(UserSocialCreated $event): void
    {
        if ($event->social->user && method_exists($event->social->user, 'logSocialConnected')) {
            $event->social->user->logSocialConnected($event->social->provider);
        }
    }
}
