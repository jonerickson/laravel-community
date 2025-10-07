<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class SetLastSeenTimestamp
{
    public function handle(Login $event): void
    {
        if (($user = $event->user) && $user instanceof User) {
            $user->updateQuietly([
                'last_seen_at' => now(),
            ]);
        }
    }
}
