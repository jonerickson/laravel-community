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
                OrderStatus::Cancelled => OrderCancelled::dispatch($event->order),
                OrderStatus::Pending => OrderPending::dispatch($event->order),
                OrderStatus::Processing => OrderProcessing::dispatch($event->order),
                OrderStatus::Succeeded => OrderSucceeded::dispatch($event->order),
                default => null
            };
        }
    }
}
