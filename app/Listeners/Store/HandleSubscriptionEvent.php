<?php

declare(strict_types=1);

namespace App\Listeners\Store;

use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use App\Mail\Subscriptions\SubscriptionCreated as SubscriptionCreatedMail;
use App\Mail\Subscriptions\SubscriptionDeleted as SubscriptionDeletedMail;
use App\Mail\Subscriptions\SubscriptionUpdated as SubscriptionUpdatedMail;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Traits\Conditionable;

class HandleSubscriptionEvent implements ShouldQueue
{
    use Conditionable;
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        match ($event::class) {
            SubscriptionCreated::class => $this->handleSubscriptionCreated($event),
            SubscriptionUpdated::class => $this->handleSubscriptionUpdated($event),
            SubscriptionDeleted::class => $this->handleSubscriptionDeleted($event),
            default => null,
        };
    }

    private function handleSubscriptionCreated(SubscriptionCreated $event): void
    {
        $this->sendMail(
            mailable: new SubscriptionCreatedMail($event->user, $event->product),
            user: $event->user
        );
    }

    private function handleSubscriptionUpdated(SubscriptionUpdated $event): void
    {
        $this->when(
            value: filled($event->previousStatus) && $event->currentStatus !== $event->previousStatus,
            callback: fn (HandleSubscriptionEvent $eventHandler) => $eventHandler->sendMail(
                mailable: new SubscriptionUpdatedMail($event->user, $event->product, $event->currentStatus, $event->previousStatus),
                user: $event->user
            )
        );
    }

    private function handleSubscriptionDeleted(SubscriptionDeleted $event): void
    {
        $this->sendMail(
            mailable: new SubscriptionDeletedMail($event->user, $event->product),
            user: $event->user
        );
    }

    private function sendMail(Mailable $mailable, User $user): void
    {
        Mail::to($user->email)->send($mailable);
    }
}
