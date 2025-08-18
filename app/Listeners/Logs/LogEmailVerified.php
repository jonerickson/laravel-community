<?php

declare(strict_types=1);

namespace App\Listeners\Logs;

use Illuminate\Auth\Events\Verified;

class LogEmailVerified
{
    public function handle(Verified $event): void
    {
        if ($event->user && method_exists($event->user, 'logEmailVerification')) {
            $event->user->logEmailVerification();
        }
    }
}
