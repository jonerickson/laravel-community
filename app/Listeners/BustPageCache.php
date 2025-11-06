<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PageUpdated;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class BustPageCache
{
    public function handle(PageUpdated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        Cache::forget('navigation_pages');
    }
}
