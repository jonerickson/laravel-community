<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Events\OrderPending;
use App\Events\OrderProcessing;
use App\Events\OrderRefunded;
use App\Events\OrderSaved;
use App\Events\OrderSucceeded;
use Illuminate\Support\Facades\App;

class DispatchOrderStatusEvents
{
    public function handle(OrderSaved $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event->order->wasChanged('status')) {
            match ($event->order->status) {
                OrderStatus::Cancelled => event(new OrderCancelled($event->order)),
                OrderStatus::Pending => event(new OrderPending($event->order)),
                OrderStatus::Processing => event(new OrderProcessing($event->order)),
                OrderStatus::Refunded => event(new OrderRefunded($event->order)),
                OrderStatus::Succeeded => event(new OrderSucceeded($event->order)),
                default => null
            };
        }
    }
}
