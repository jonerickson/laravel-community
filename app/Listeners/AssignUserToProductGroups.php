<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderSucceeded;
use App\Events\SubscriptionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class AssignUserToProductGroups implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(SubscriptionCreated|OrderSucceeded $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $user = $event instanceof SubscriptionCreated ? $event->user : $event->order->user;

        if (! $user) {
            return;
        }

        $user->syncGroups();
    }
}
