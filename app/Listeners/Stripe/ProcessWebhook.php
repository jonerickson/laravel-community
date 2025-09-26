<?php

declare(strict_types=1);

namespace App\Listeners\Stripe;

use App\Enums\OrderRefundReason;
use App\Enums\OrderStatus;
use App\Events\Stripe\CustomerDeleted;
use App\Events\Stripe\CustomerUpdated;
use App\Events\Stripe\PaymentActionRequired;
use App\Events\Stripe\PaymentSucceeded;
use App\Events\Stripe\RefundCreated;
use App\Events\Stripe\SubscriptionCreated;
use App\Events\Stripe\SubscriptionDeleted;
use App\Events\Stripe\SubscriptionUpdated;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Price;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProcessWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    private ?array $payload = null;

    private ?User $customer = null;

    private ?string $orderId = null;

    public function __construct(protected PaymentManager $paymentManager)
    {
        //
    }

    public function handle(CustomerDeleted|CustomerUpdated|PaymentActionRequired|PaymentSucceeded|SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted|RefundCreated $event): void
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
            $event instanceof RefundCreated => $this->handleRefundCreated(),
        };
    }

    public function tags(CustomerDeleted|CustomerUpdated|PaymentActionRequired|PaymentSucceeded|SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted|RefundCreated $event): array
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
        if (! $this->customer = $this->resolveCustomer()) {
            return;
        }

        if (! $this->orderId = $this->resolveOrderId()) {
            return;
        }

        $order = Order::firstOrCreate([
            'reference_id' => $this->orderId,
        ], [
            'user_id' => $this->customer->getKey(),
            'status' => OrderStatus::Succeeded,
            'invoice_url' => data_get($this->payload, 'data.object.hosted_invoice_url'),
            'external_invoice_id' => data_get($this->payload, 'data.object.id'),
        ]);

        if ($order->wasRecentlyCreated) {
            foreach (Arr::wrap(data_get($this->payload, 'data.object.lines.data')) as $lineItem) {
                $price = Price::firstOrCreate([
                    'external_price_id' => data_get($lineItem, 'pricing.price_details.price'),
                ], [
                    'name' => data_get($lineItem, 'description'),
                ]);

                $order->items()->create([
                    'quantity' => data_get($lineItem, 'quantity') ?? 1,
                    'product_id' => $price->product?->getKey(),
                    'price_id' => $price->getKey(),
                ]);
            }
        }
    }

    protected function handlePaymentActionRequired(): void
    {
        if (! $this->customer = $this->resolveCustomer()) {
            return;
        }

        if (! $this->orderId = $this->resolveOrderId()) {
            return;
        }

        Order::updateOrCreate([
            'reference_id' => $this->orderId,
        ], [
            'user_id' => $this->customer->getKey(),
            'status' => OrderStatus::RequiresAction,
            'invoice_url' => data_get($this->payload, 'data.object.hosted_invoice_url'),
            'external_invoice_id' => data_get($this->payload, 'data.object.id'),
        ]);
    }

    protected function handleRefundCreated(): void
    {
        if (! $paymentIntendId = $this->resolvePaymentIntendId()) {
            return;
        }

        $order = Order::query()->where('external_order_id', $paymentIntendId)->first();

        if (blank($order) || $order->status === OrderStatus::Refunded) {
            return;
        }

        $order->update([
            'status' => OrderStatus::Refunded,
            'refund_reason' => OrderRefundReason::tryFrom(data_get($this->payload, 'data.object.reason')) ?? OrderRefundReason::Other,
            'refund_notes' => data_get($this->payload, 'data.object.reason'),
        ]);
    }

    private function resolveCustomer(): ?User
    {
        return User::query()
            ->where('stripe_id', data_get($this->payload, 'data.object.customer'))
            ->first();
    }

    private function resolvePaymentIntendId()
    {
        return match (data_get($this->payload, 'type')) {
            'refund.created' => data_get($this->payload, 'data.object.payment_intent'),
            default => null,
        };
    }

    private function resolveOrderId()
    {
        return match (data_get($this->payload, 'type')) {
            'invoice.payment_succeeded' => data_get(Collection::make(data_get($this->payload, 'data.object.lines.data'))->firstWhere(fn (array $item) => filled(data_get($item, 'metadata.order_id'))), 'metadata.order_id'),
            default => null,
        };
    }
}
