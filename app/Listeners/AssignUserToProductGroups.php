<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderSucceeded;
use App\Events\SubscriptionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssignUserToProductGroups implements ShouldQueue
{
    use Queueable;

    public function handle(SubscriptionCreated|OrderSucceeded $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (! $user) {
            return;
        }

        $user->syncGroups();
    }
}
