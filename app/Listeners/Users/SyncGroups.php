<?php

declare(strict_types=1);

namespace App\Listeners\Users;

use App\Events\OrderCancelled;
use App\Events\OrderRefunded;
use App\Events\OrderSucceeded;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;

class SyncGroups implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(Login|SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted|OrderSucceeded|OrderCancelled|OrderRefunded $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        $user = match ($event::class) {
            SubscriptionCreated::class, SubscriptionUpdated::class, SubscriptionDeleted::class => $event->user,
            OrderSucceeded::class, OrderCancelled::class, OrderRefunded::class => $event->order->user,
            Login::class => $event->user,
        };

        if (! $user instanceof User) {
            return;
        }

        $user->syncGroups();
    }
}
