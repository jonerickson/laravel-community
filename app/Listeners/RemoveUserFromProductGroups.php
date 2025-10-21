<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\SubscriptionDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveUserFromProductGroups implements ShouldQueue
{
    use Queueable;

    public function handle(SubscriptionDeleted|OrderCancelled $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (! $user) {
            return;
        }

        $user->syncGroups();
    }
}
