<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RemoveUserFromProductGroups implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(SubscriptionDeleted $event): void
    {
        $order = $event->order;
        $user = $order->user;

        $products = $order->products()->with('groups')->get();

        foreach ($products as $product) {
            $groups = $product->groups;

            if ($groups->isEmpty()) {
                continue;
            }

            foreach ($groups as $group) {
                $user->removeFromGroup($group);
            }
        }
    }
}
