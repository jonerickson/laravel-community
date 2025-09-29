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

class HandleSubscriptionEvent
{
    public function handle(SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted $event): void
    {
        match ($event::class) {
            SubscriptionCreated::class => $this->sendMail(
                new SubscriptionCreatedMail($event->order),
                $event->order
            ),
            SubscriptionUpdated::class => $this->sendMail(
                new SubscriptionUpdatedMail($event->order, $event->status),
                $event->order
            ),
            SubscriptionDeleted::class => $this->sendMail(
                new SubscriptionDeletedMail($event->order),
                $event->order
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
