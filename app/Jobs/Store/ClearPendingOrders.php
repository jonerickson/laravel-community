<?php

declare(strict_types=1);

namespace App\Jobs\Store;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ClearPendingOrders implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Order::query()
            ->where('status', OrderStatus::Pending)
            ->whereDoesntHave('items')
            ->where('created_at', '<', now()->subDay())
            ->delete();
    }
}
