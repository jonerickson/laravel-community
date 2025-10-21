<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PageUpdated;
use Illuminate\Support\Facades\Cache;

class BustPageCache
{
    public function handle(PageUpdated $event): void
    {
        Cache::forget('navigation_pages');
    }
}
