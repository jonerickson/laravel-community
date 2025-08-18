<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    public function handle(Login $event): void
    {
        if ($event->user && method_exists($event->user, 'logLogin')) {
            $event->user->logLogin(request());
        }
    }
}
