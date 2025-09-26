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
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    private ?array $payload = null;

    public function handle(CustomerDeleted|CustomerUpdated|PaymentActionRequired|PaymentSucceeded|SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted $event): void
    {
        $this->payload = $event->payload;

        match (true) {
            $event instanceof PaymentSucceeded => $this->handlePaymentSucceeded(),
            $event instanceof PaymentActionRequired => $this->handlePaymentActionRequired(),
            $event instanceof SubscriptionDeleted => $this->handleSubscriptionDeleted(),
            $event instanceof SubscriptionUpdated => $this->handleSubscriptionUpdated(),
            $event instanceof SubscriptionCreated => $this->handleSubscriptionCreated(),
            $event instanceof CustomerUpdated => $this->handleCustomerUpdated(),
            $event instanceof CustomerDeleted => $this->handleCustomerDeleted(),
        };
    }

    public function tags(CustomerDeleted|CustomerUpdated|PaymentActionRequired|PaymentSucceeded|SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted $event): array
    {
        return array_filter(['stripe', data_get($event->payload, 'type')]);
    }

    protected function handleCustomerDeleted(): void {}

    protected function handleCustomerUpdated(): void {}

    protected function handleSubscriptionCreated(): void {}

    protected function handleSubscriptionUpdated(): void {}

    protected function handleSubscriptionDeleted(): void {}

    protected function handlePaymentSucceeded(): void
    {
        if (! $customer = $this->resolveCustomer()) {
            return;
        }

        if (! $orderId = $this->resolveOrderId()) {
            return;
        }

        Order::updateOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $customer->getKey(),
            'status' => OrderStatus::Succeeded,
            'amount' => data_get($this->payload, 'data.object.amount_paid'),
            'invoice_url' => data_get($this->payload, 'data.object.hosted_invoice_url'),
            'external_invoice_id' => data_get($this->payload, 'data.object.id'),
        ]);
    }

    protected function handlePaymentActionRequired(): void
    {
        if (! $customer = $this->resolveCustomer()) {
            return;
        }

        if (! $orderId = $this->resolveOrderId()) {
            return;
        }

        Order::updateOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $customer->getKey(),
            'status' => OrderStatus::RequiresAction,
            'amount' => data_get($this->payload, 'data.object.amount_paid'),
            'invoice_url' => data_get($this->payload, 'data.object.hosted_invoice_url'),
            'external_invoice_id' => data_get($this->payload, 'data.object.id'),
        ]);
    }

    private function resolveCustomer(): ?User
    {
        return User::query()
            ->where('stripe_id', data_get($this->payload, 'data.object.customer'))
            ->first();
    }

    private function resolveOrderId(): ?string
    {
        return data_get($this->payload, 'data.object.metadata.order_id');
    }
}
