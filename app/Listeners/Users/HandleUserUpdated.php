<?php

declare(strict_types=1);

namespace App\Listeners\Users;

use App\Events\PasswordChanged;
use App\Events\UserUpdated;
use Illuminate\Support\Facades\App;

class HandleUserUpdated
{
    public function handle(UserUpdated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event->user->wasChanged('password')) {
            event(new PasswordChanged($event->user));
        }
    }
}
