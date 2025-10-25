<?php

declare(strict_types=1);

namespace App\Listeners\Stripe;

use App\Enums\OrderRefundReason;
use App\Enums\SubscriptionStatus;
use App\Events\CustomerDeleted;
use App\Events\CustomerUpdated;
use App\Events\PaymentActionRequired;
use App\Events\PaymentSucceeded;
use App\Events\RefundCreated;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use App\Managers\PaymentManager;
use App\Models\Order;
use App\Models\Price;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Events\WebhookReceived;

class HandleWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    private ?User $user = null;

    private ?array $payload = null;

    public function __construct(private readonly PaymentManager $paymentManager)
    {
        //
    }

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
            'invoice.payment_action_required' => event(new PaymentActionRequired($order, $this->resolvePaymentConfirmationUrl($order))),
            'customer.subscription.created' => event(new SubscriptionCreated($order)),
            'customer.subscription.updated' => event(new SubscriptionUpdated($order, SubscriptionStatus::tryFrom(data_get($event->payload, 'data.object.status') ?? ''), SubscriptionStatus::tryFrom(data_get($event->payload, 'data.previous_attributes.status') ?? ''))),
            'customer.subscription.deleted' => event(new SubscriptionDeleted($order)),
            'customer.updated' => event(new CustomerUpdated($this->user)),
            'customer.deleted' => event(new CustomerDeleted($this->user)),
            'refund.created' => event(new RefundCreated($order, OrderRefundReason::tryFrom(data_get($event->payload, 'data.object.reason') ?? '') ?? OrderRefundReason::Other, data_get($event->payload, 'data.object.reason'))),
            default => null,
        };
    }

    public function tags(): array
    {
        return array_filter(['stripe', data_get($this->payload, 'type')]);
    }

    protected function handleSubscriptionCreated(): ?Order
    {
        if (blank($orderId = $this->resolveOrderId())) {
            return null;
        }

        return Order::updateOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $this->user->getKey(),
            'external_order_id' => data_get($this->payload, 'data.object.id'),
            'external_payment_id' => data_get($this->payload, 'data.object.default_payment_method'),
            'external_invoice_id' => data_get($this->payload, 'data.object.latest_invoice'),
            'external_event_id' => data_get($this->payload, 'id'),
        ]);
    }

    protected function handleSubscriptionUpdated(): ?Order
    {
        if (blank($orderId = $this->resolveOrderId())) {
            return null;
        }

        return Order::updateOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $this->user->getKey(),
            'external_order_id' => data_get($this->payload, 'data.object.id'),
            'external_payment_id' => data_get($this->payload, 'data.object.default_payment_method'),
            'external_invoice_id' => data_get($this->payload, 'data.object.latest_invoice'),
            'external_event_id' => data_get($this->payload, 'id'),
        ]);
    }

    protected function handleSubscriptionDeleted(): ?Order
    {
        if (blank($orderId = $this->resolveOrderId())) {
            return null;
        }

        return Order::updateOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $this->user->getKey(),
            'external_order_id' => data_get($this->payload, 'data.object.id'),
            'external_payment_id' => data_get($this->payload, 'data.object.default_payment_method'),
            'external_invoice_id' => data_get($this->payload, 'data.object.latest_invoice'),
            'external_event_id' => data_get($this->payload, 'id'),
        ]);
    }

    protected function handlePaymentEvent(): ?Order
    {
        if (blank($orderId = $this->resolveOrderId())) {
            $orderId = Str::uuid()->toString();
        }

        $order = Order::updateOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $this->user->getKey(),
            'amount_due' => ((int) data_get($this->payload, 'data.object.amount_due') ?? 0) / 100,
            'amount_overpaid' => ((int) data_get($this->payload, 'data.object.amount_overpaid') ?? 0) / 100,
            'amount_paid' => ((int) data_get($this->payload, 'data.object.amount_paid') ?? 0) / 100,
            'amount_remaining' => ((int) data_get($this->payload, 'data.object.amount_remaining') ?? 0) / 100,
            'invoice_url' => data_get($this->payload, 'data.object.hosted_invoice_url'),
            'external_invoice_id' => data_get($this->payload, 'data.object.id'),
            'external_event_id' => data_get($this->payload, 'id'),
        ]);

        $lineItems = Arr::wrap(data_get($this->payload, 'data.object.lines.data'));

        if ($order->wasRecentlyCreated) {
            foreach ($lineItems as $lineItem) {
                $price = Price::firstWhere([
                    'external_price_id' => data_get($lineItem, 'pricing.price_details.price'),
                ]);

                $item = [
                    'quantity' => data_get($lineItem, 'quantity') ?? 1,
                    'amount' => ((int) data_get($lineItem, 'amount') ?? 0) / 100,
                ];

                if ($price) {
                    $item['price_id'] = $price->getKey();
                } else {
                    $item['name'] = data_get($lineItem, 'description') ?? 'Unknown Product';
                }

                $order->items()->create($item);
            }
        } else {
            foreach ($lineItems as $lineItem) {
                $price = Price::firstWhere([
                    'external_price_id' => data_get($lineItem, 'pricing.price_details.price'),
                ]);

                $orderItem = $order->items()
                    ->when($price, fn ($query) => $query->where('price_id', $price->getKey()))
                    ->first();

                $orderItem?->update([
                    'amount' => ((int) data_get($lineItem, 'amount') ?? 0) / 100,
                    'quantity' => data_get($lineItem, 'quantity') ?? 1,
                ]);
            }
        }

        return $order;
    }

    protected function handleRefundCreated(): ?Order
    {
        if (blank($paymentIntendId = $this->resolvePaymentIntendId())) {
            return null;
        }

        return Order::updateOrCreate([
            'external_order_id' => $paymentIntendId,
        ], [
            'user_id' => $this->user->getKey(),
            'external_event_id' => data_get($this->payload, 'id'),
        ]);
    }

    private function resolveOrder(): ?Order
    {
        return match (data_get($this->payload, 'type')) {
            'invoice.payment_succeeded', 'invoice.payment_action_required' => $this->handlePaymentEvent(),
            'customer.subscription.created' => $this->handleSubscriptionCreated(),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated(),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted(),
            'refund.created' => $this->handleRefundCreated(),
            default => null,
        };
    }

    private function resolveCustomer(): ?User
    {
        return match (data_get($this->payload, 'type')) {
            default => User::query()
                ->where('stripe_id', data_get($this->payload, 'data.object.customer'))
                ->first(),
        };
    }

    private function resolvePaymentIntendId(): ?string
    {
        return match (data_get($this->payload, 'type')) {
            'refund.created', 'invoice.payment_action_required' => data_get($this->payload, 'data.object.payment_intent'),
            default => null,
        };
    }

    private function resolveOrderId(): ?string
    {
        return match (data_get($this->payload, 'type')) {
            'invoice.payment_succeeded' => $this->findOrderIdForInvoice(),
            'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted' => data_get($this->payload, 'data.object.metadata.order_id'),
            default => null,
        };
    }

    private function findOrderIdForInvoice(): ?string
    {
        // First try: Check invoice metadata for one-off purchases
        $orderId = data_get($this->payload, 'data.object.metadata.order_id');

        if (filled($orderId)) {
            return $orderId;
        }

        // Second try: Check line items metadata for subscription purchases
        $lineItems = Collection::make(data_get($this->payload, 'data.object.lines.data'));

        $lineItemWithOrderId = $lineItems->first(fn (array $item): bool => filled(data_get($item, 'metadata.order_id')));

        return data_get($lineItemWithOrderId, 'metadata.order_id');
    }

    private function resolvePaymentConfirmationUrl(Order $order): ?string
    {
        if (blank($invoice = $this->paymentManager->findInvoice($order))) {
            return null;
        }

        return $invoice->invoiceUrl;
    }
}
