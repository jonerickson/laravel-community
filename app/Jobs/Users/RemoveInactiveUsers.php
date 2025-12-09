<?php

declare(strict_types=1);

namespace App\Jobs\Users;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveInactiveUsers implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        User::query()
            ->whereNull('email_verified_at')
            ->whereNull('last_seen_at')
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
    }
}
