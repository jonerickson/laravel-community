<?php

declare(strict_types=1);

namespace App\Jobs\Api;

use App\Models\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ClearLogs implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Log::query()
            ->where('created_at', '<', now()->subDays(30))
            ->delete();
    }
}
