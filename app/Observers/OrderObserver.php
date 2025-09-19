<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPending;
use App\Events\OrderProcessing;
use App\Events\OrderSucceeded;
use App\Models\Order;

class OrderObserver
{
    public function saving(Order $order): void
    {
        if ($order->isDirty('status')) {
            match ($order->status) {
                OrderStatus::Cancelled => event(new OrderCancelled($order)),
                OrderStatus::Pending => event(new OrderPending($order)),
                OrderStatus::Processing => event(new OrderProcessing($order)),
                OrderStatus::Succeeded => event(new OrderSucceeded($order)),
                default => null
            };
        }
    }
}
