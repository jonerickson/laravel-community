<?php

declare(strict_types=1);

namespace App\Listeners\Stripe;

use App\Enums\OrderStatus;
use App\Events\Stripe\CustomerDeleted;
use App\Events\Stripe\CustomerUpdated;
use App\Events\Stripe\PaymentActionRequired;
use App\Events\Stripe\PaymentSucceeded;
use App\Events\Stripe\SubscriptionCreated;
use App\Events\Stripe\SubscriptionDeleted;
use App\Events\Stripe\SubscriptionUpdated;
use App\Models\Order;

class ProcessWebhook
{
    public function handle(CustomerDeleted|CustomerUpdated|PaymentActionRequired|PaymentSucceeded|SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted $event): void
    {
        match (true) {
            $event instanceof PaymentSucceeded => $this->handlePaymentSucceeded($event),
            $event instanceof PaymentActionRequired => $this->handlePaymentActionRequired($event),
            $event instanceof SubscriptionDeleted => $this->handleSubscriptionDeleted($event),
            $event instanceof SubscriptionUpdated => $this->handleSubscriptionUpdated($event),
            $event instanceof SubscriptionCreated => $this->handleSubscriptionCreated($event),
            $event instanceof CustomerUpdated => $this->handleCustomerUpdated($event),
            $event instanceof CustomerDeleted => $this->handleCustomerDeleted($event),
        };
    }

    protected function handleCustomerDeleted(CustomerDeleted $event): void {}

    protected function handleCustomerUpdated(CustomerUpdated $event): void {}

    protected function handleSubscriptionCreated(SubscriptionCreated $event): void {}

    protected function handleSubscriptionUpdated(SubscriptionUpdated $event): void {}

    protected function handleSubscriptionDeleted(SubscriptionDeleted $event): void {}

    protected function handlePaymentSucceeded(PaymentSucceeded $event): void
    {
        Order::firstOrCreate([
            'reference_id' => data_get($event->payload, 'data.object.metadata.order_id'),
        ], [
            'status' => OrderStatus::Succeeded,
            'amount' => data_get($event->payload, 'data.object.amount_paid'),
            'invoice_url' => data_get($event->payload, 'data.object.hosted_invoice_url'),
            'external_invoice_id' => data_get($event->payload, 'data.object.id'),
        ]);
    }

    protected function handlePaymentActionRequired(PaymentActionRequired $event): void {}
}
