<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CustomerDeleted;
use App\Events\CustomerUpdated;
use App\Events\PaymentActionRequired;
use App\Events\PaymentSucceeded;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Events\WebhookReceived;

class HandleCashierWebhook
{
    public function handle(WebhookReceived $event): void
    {
        match ($event->payload['type']) {
            'invoice.payment_succeeded' => Event::dispatch(new PaymentSucceeded),
            'invoice.payment_action_required' => Event::dispatch(new PaymentActionRequired),
            'customer.subscription.created' => Event::dispatch(new SubscriptionCreated),
            'customer.subscription.updated' => Event::dispatch(new SubscriptionUpdated),
            'customer.subscription.deleted' => Event::dispatch(new SubscriptionDeleted),
            'customer.updated' => Event::dispatch(new CustomerUpdated),
            'customer.deleted' => Event::dispatch(new CustomerDeleted),
        };
    }
}
