<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPending;
use App\Events\OrderProcessing;
use App\Events\OrderSaving;
use App\Events\OrderSucceeded;

class HandleOrderSaving
{
    public function handle(OrderSaving $event): void
    {
        if ($event->order->isDirty('status')) {
            match ($event->order->status) {
                OrderStatus::Cancelled => event(new OrderCancelled($event->order)),
                OrderStatus::Pending => event(new OrderPending($event->order)),
                OrderStatus::Processing => event(new OrderProcessing($event->order)),
                OrderStatus::Succeeded => event(new OrderSucceeded($event->order)),
                default => null
            };
        }
    }
}
