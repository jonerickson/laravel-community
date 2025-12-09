<?php

declare(strict_types=1);

namespace App\Jobs\Api;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Activitylog\Models\Activity;

class ClearActivity implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Activity::query()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
    }
}
