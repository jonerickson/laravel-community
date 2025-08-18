<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user && method_exists($event->user, 'logLogout')) {
            $event->user->logLogout();
        }
    }
}
