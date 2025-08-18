<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use Illuminate\Auth\Events\Registered;

class LogRegistration
{
    public function handle(Registered $event): void
    {
        if ($event->user && method_exists($event->user, 'logAccountRegistration')) {
            $event->user->logAccountRegistration();
        }
    }
}
