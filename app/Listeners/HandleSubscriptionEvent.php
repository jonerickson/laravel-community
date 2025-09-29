<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use App\Mail\Subscriptions\SubscriptionCreated as SubscriptionCreatedMail;
use App\Mail\Subscriptions\SubscriptionDeleted as SubscriptionDeletedMail;
use App\Mail\Subscriptions\SubscriptionUpdated as SubscriptionUpdatedMail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Traits\Conditionable;

class HandleSubscriptionEvent
{
    use Conditionable;

    public function handle(SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted $event): void
    {
        match ($event::class) {
            SubscriptionCreated::class => $this->sendMail(
                mailable: new SubscriptionCreatedMail($event->order),
                order: $event->order
            ),
            SubscriptionUpdated::class => $this->when(
                value: filled($event->previousStatus) && $event->currentStatus !== $event->previousStatus,
                callback: fn (HandleSubscriptionEvent $eventHandler) => $eventHandler->sendMail(
                    mailable: new SubscriptionUpdatedMail($event->order, $event->currentStatus),
                    order: $event->order
                )
            ),
            SubscriptionDeleted::class => $this->sendMail(
                mailable: new SubscriptionDeletedMail($event->order),
                order: $event->order
            ),
            default => null,
        };
    }

    protected function sendMail(Mailable $mailable, $order): void
    {
        if ($order->user) {
            Mail::to($order->user->email)->send($mailable);
        }
    }
}
