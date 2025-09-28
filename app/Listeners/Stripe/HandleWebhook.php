<?php

declare(strict_types=1);

namespace App\Listeners\Stripe;

use App\Enums\OrderRefundReason;
use App\Events\CustomerDeleted;
use App\Events\CustomerUpdated;
use App\Events\PaymentActionRequired;
use App\Events\PaymentSucceeded;
use App\Events\RefundCreated;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use App\Models\Order;
use App\Models\Price;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class HandleWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    private ?User $user = null;

    private array $payload;

    public function handle(WebhookReceived $event): void
    {
        Log::debug('Stripe webhook', $event->payload);

        $this->payload = $event->payload;
        $this->user = $this->resolveCustomer();

        if (blank($this->user)) {
            return;
        }

        $order = $this->resolveOrder();

        if (blank($order)) {
            return;
        }

        match (data_get($event->payload, 'type')) {
            'invoice.payment_succeeded' => event(new PaymentSucceeded($order)),
            'invoice.payment_action_required' => event(new PaymentActionRequired($order)),
            'customer.subscription.created' => event(new SubscriptionCreated($order)),
            'customer.subscription.updated' => event(new SubscriptionUpdated($order)),
            'customer.subscription.deleted' => event(new SubscriptionDeleted($order)),
            'customer.updated' => event(new CustomerUpdated($this->user)),
            'customer.deleted' => event(new CustomerDeleted($this->user)),
            'refund.created' => event(new RefundCreated(
                order: $order,
                reason: OrderRefundReason::tryFrom(data_get($event->payload, 'data.object.reason')) ?? OrderRefundReason::Other,
                notes: data_get($event->payload, 'data.object.reason')
            )),
            default => null,
        };
    }

    public function tags(WebhookReceived $event): array
    {
        return array_filter(['stripe', data_get($event->payload, 'type')]);
    }

    protected function handleCustomerDeleted(): void {}

    protected function handleCustomerUpdated(): void {}

    protected function handleSubscriptionCreated(): void {}

    protected function handleSubscriptionUpdated(): void {}

    protected function handleSubscriptionDeleted(): void {}

    protected function handlePaymentSucceeded(): ?Order
    {
        if ((($orderId = $this->resolveOrderId())) === null || (($orderId = $this->resolveOrderId())) === '' || (($orderId = $this->resolveOrderId())) === '0') {
            return null;
        }

        $order = Order::firstOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $this->user->getKey(),
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

        return $order;
    }

    protected function handlePaymentActionRequired(): ?Order
    {
        if ((($orderId = $this->resolveOrderId())) === null || (($orderId = $this->resolveOrderId())) === '' || (($orderId = $this->resolveOrderId())) === '0') {
            return null;
        }

        return Order::firstOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $this->user->getKey(),
            'invoice_url' => data_get($this->payload, 'data.object.hosted_invoice_url'),
            'external_invoice_id' => data_get($this->payload, 'data.object.id'),
        ]);
    }

    protected function handleRefundCreated(): ?Order
    {
        if ((($paymentIntendId = $this->resolvePaymentIntendId())) === null || (($paymentIntendId = $this->resolvePaymentIntendId())) === '' || (($paymentIntendId = $this->resolvePaymentIntendId())) === '0') {
            return null;
        }

        return Order::firstOrCreate([
            'external_order_id' => $paymentIntendId,
        ], [
            'user_id' => $this->user->getKey(),
        ]);
    }

    private function resolveOrder(): ?Order
    {
        return match (data_get($this->payload, 'type')) {
            'invoice.payment_succeeded' => $this->handlePaymentSucceeded(),
            'invoice.payment_action_required' => $this->handlePaymentActionRequired(),
            'refund.created' => $this->handleRefundCreated(),
            default => null,
        };
    }

    private function resolveCustomer(): ?User
    {
        return User::query()
            ->where('stripe_id', data_get($this->payload, 'data.object.customer'))
            ->first();
    }

    private function resolvePaymentIntendId(): ?string
    {
        return match (data_get($this->payload, 'type')) {
            'refund.created' => data_get($this->payload, 'data.object.payment_intent'),
            default => null,
        };
    }

    private function resolveOrderId(): ?string
    {
        return match (data_get($this->payload, 'type')) {
            'invoice.payment_succeeded' => data_get(Collection::make(data_get($this->payload, 'data.object.lines.data'))->firstWhere(fn (array $item): bool => filled(data_get($item, 'metadata.order_id'))), 'metadata.order_id'),
            default => null,
        };
    }
}
