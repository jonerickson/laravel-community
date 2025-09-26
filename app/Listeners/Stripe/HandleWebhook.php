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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class HandleWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(WebhookReceived $event): void
    {
        Log::debug('Stripe webhook', $event->payload);

        match (data_get($event->payload, 'type')) {
            'invoice.payment_succeeded' => PaymentSucceeded::dispatch($event->payload),
            'invoice.payment_action_required' => PaymentActionRequired::dispatch($event->payload),
            'customer.subscription.created' => SubscriptionCreated::dispatch($event->payload),
            'customer.subscription.updated' => SubscriptionUpdated::dispatch($event->payload),
            'customer.subscription.deleted' => SubscriptionDeleted::dispatch($event->payload),
            'customer.updated' => CustomerUpdated::dispatch($event->payload),
            'customer.deleted' => CustomerDeleted::dispatch($event->payload),
            default => null,
        };
    }
}
