<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PasswordChanged;
use App\Events\UserUpdated;

class HandleUserUpdated
{
    public function handle(UserUpdated $event): void
    {
        if ($event->user->wasChanged('password')) {
            event(new PasswordChanged($event->user));
        }
    }
}
