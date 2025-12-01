<?php

declare(strict_types=1);

namespace App\Jobs\Store;

use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReleaseExpiredInventoryReservations implements ShouldQueue
{
    use Queueable;

    public function handle(InventoryService $inventoryService): void
    {
        $inventoryService->releaseExpiredReservations();
    }
}
