<?php

declare(strict_types=1);

namespace App\Listeners\Users;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\App;

class SetLastSeenTimestamp
{
    public function handle(Login $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if (($user = $event->user) && $user instanceof User) {
            $user->updateQuietly([
                'last_seen_at' => now(),
            ]);
        }
    }
}
