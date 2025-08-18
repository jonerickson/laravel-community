<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use Illuminate\Auth\Events\PasswordReset;

class LogPasswordReset
{
    public function handle(PasswordReset $event): void
    {
        if ($event->user && method_exists($event->user, 'logPasswordResetCompleted')) {
            $event->user->logPasswordResetCompleted();
        }
    }
}
