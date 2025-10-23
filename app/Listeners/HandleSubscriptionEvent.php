<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use App\Mail\Subscriptions\SubscriptionCreated as SubscriptionCreatedMail;
use App\Mail\Subscriptions\SubscriptionDeleted as SubscriptionDeletedMail;
use App\Mail\Subscriptions\SubscriptionUpdated as SubscriptionUpdatedMail;
use App\Models\Order;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Traits\Conditionable;

class HandleSubscriptionEvent implements ShouldQueue
{
    use Conditionable;
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted $event): void
    {
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
            mailable: new SubscriptionCreatedMail($event->order),
            order: $event->order
        );
    }

    private function handleSubscriptionUpdated(SubscriptionUpdated $event): void
    {
        $this->when(
            value: filled($event->previousStatus) && $event->currentStatus !== $event->previousStatus,
            callback: fn (HandleSubscriptionEvent $eventHandler) => $eventHandler->sendMail(
                mailable: new SubscriptionUpdatedMail($event->order, $event->currentStatus),
                order: $event->order
            )
        );
    }

    private function handleSubscriptionDeleted(SubscriptionDeleted $event): void
    {
        $this->sendMail(
            mailable: new SubscriptionDeletedMail($event->order),
            order: $event->order
        );
    }

    private function sendMail(Mailable $mailable, Order $order): void
    {
        if ($order->user) {
            Mail::to($order->user->email)->send($mailable);
        }
    }
}
