<?php

declare(strict_types=1);

namespace App\Listeners\Stripe;

use App\Events\Stripe\CustomerDeleted;
use App\Events\Stripe\CustomerUpdated;
use App\Events\Stripe\PaymentActionRequired;
use App\Events\Stripe\PaymentSucceeded;
use App\Events\Stripe\SubscriptionCreated;
use App\Events\Stripe\SubscriptionDeleted;
use App\Events\Stripe\SubscriptionUpdated;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class HandleWebhook
{
    public function handle(WebhookReceived $event): void
    {
        Log::debug('Stripe webhook', $event->payload);

        match ($event->payload['type']) {
            'invoice.payment_succeeded' => event(new PaymentSucceeded($event->payload)),
            'invoice.payment_action_required' => event(new PaymentActionRequired($event->payload)),
            'customer.subscription.created' => event(new SubscriptionCreated($event->payload)),
            'customer.subscription.updated' => event(new SubscriptionUpdated($event->payload)),
            'customer.subscription.deleted' => event(new SubscriptionDeleted($event->payload)),
            'customer.updated' => event(new CustomerUpdated($event->payload)),
            'customer.deleted' => event(new CustomerDeleted($event->payload)),
        };
    }
}
