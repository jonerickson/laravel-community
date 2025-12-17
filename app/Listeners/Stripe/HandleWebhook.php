<?php

declare(strict_types=1);

namespace App\Listeners\Stripe;

use App\Data\InvoiceData;
use App\Enums\BillingReason;
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
use App\Models\Discount;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
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

        $product = $this->resolveProduct();

        if ($product instanceof Product) {
            match (data_get($event->payload, 'type')) {
                'customer.subscription.created' => event(new SubscriptionCreated($this->user, $product)),
                'customer.subscription.updated' => event(new SubscriptionUpdated($this->user, $product, SubscriptionStatus::tryFrom(data_get($event->payload, 'data.object.status') ?? ''), SubscriptionStatus::tryFrom(data_get($event->payload, 'data.previous_attributes.status') ?? ''))),
                'customer.subscription.deleted' => event(new SubscriptionDeleted($this->user, $product)),
                'customer.updated' => event(new CustomerUpdated($this->user)),
                'customer.deleted' => event(new CustomerDeleted($this->user)),
                default => null,
            };
        }

        $order = $this->resolveOrder();

        if (blank($order)) {
            return;
        }

        match (data_get($event->payload, 'type')) {
            'invoice.payment_succeeded' => event(new PaymentSucceeded($order, BillingReason::tryFrom(data_get($event->payload, 'data.object.billing_reason')))),
            'invoice.payment_action_required' => event(new PaymentActionRequired($order, $this->resolvePaymentConfirmationUrl($order))),
            'refund.created' => event(new RefundCreated($order, OrderRefundReason::tryFrom(data_get($event->payload, 'data.object.reason') ?? '') ?? OrderRefundReason::Other, data_get($event->payload, 'data.object.reason'))),
            default => null,
        };
    }

    public function tags(): array
    {
        return array_filter(['stripe', data_get($this->payload, 'type')]);
    }

    protected function handleSubscriptionEvent(): ?Product
    {
        if (blank($productId = $this->resolveProductId())) {
            return null;
        }

        return Product::query()->where('external_product_id', $productId)->first();
    }

    protected function handlePaymentEvent(): ?Order
    {
        $orderId = $this->resolveOrderId() ?? Str::uuid()->toString();

        $order = Order::updateOrCreate([
            'reference_id' => $orderId,
        ], [
            'user_id' => $this->user->getKey(),
            'billing_reason' => BillingReason::tryFrom(data_get($this->payload, 'data.object.billing_reason')) ?? BillingReason::Manual,
            'amount_due' => ((int) data_get($this->payload, 'data.object.amount_due') ?? 0) / 100,
            'amount_overpaid' => ((int) data_get($this->payload, 'data.object.amount_overpaid') ?? 0) / 100,
            'amount_paid' => ((int) data_get($this->payload, 'data.object.amount_paid') ?? 0) / 100,
            'amount_remaining' => ((int) data_get($this->payload, 'data.object.amount_remaining') ?? 0) / 100,
            'invoice_url' => data_get($this->payload, 'data.object.hosted_invoice_url'),
            'invoice_number' => data_get($this->payload, 'data.object.number'),
            'external_invoice_id' => data_get($this->payload, 'data.object.id'),
            'external_event_id' => data_get($this->payload, 'id'),
        ]);

        $lineItems = collect(data_get($this->payload, 'data.object.lines.data', []));

        $prices = Price::whereIn(
            'external_price_id',
            $lineItems->pluck('pricing.price_details.price')->filter()->unique()
        )->get()->keyBy('external_price_id');

        foreach ($lineItems as $lineItem) {
            $externalItemId = data_get($lineItem, 'id');
            $externalPriceId = data_get($lineItem, 'pricing.price_details.price');

            $price = $prices->get($externalPriceId);
            $priceId = $price?->getKey();

            $item = [
                'description' => data_get($lineItem, 'description'),
                'quantity' => data_get($lineItem, 'quantity') ?? 1,
                'amount' => ((int) data_get($lineItem, 'amount') ?? 0) / 100,
                'external_item_id' => $externalItemId,
                'price_id' => $priceId,
            ];

            if ($order->wasRecentlyCreated) {
                $order->items()->create($item);
            } else {
                $orderItem = $order->items()
                    ->where(function (Builder $query) use ($externalItemId): void {
                        $query->where('external_item_id', $externalItemId)
                            ->orWhereNull('external_item_id');
                    })
                    ->when($priceId, fn ($query) => $query->where('price_id', $priceId))
                    ->first();

                if (! $orderItem) {
                    $order->items()->create($item);
                } else {
                    $orderItem->update([
                        'description' => data_get($lineItem, 'description'),
                        'amount' => ((int) data_get($lineItem, 'amount') ?? 0) / 100,
                        'quantity' => data_get($lineItem, 'quantity') ?? 1,
                        'external_item_id' => $externalItemId,
                    ]);
                }
            }
        }

        $invoice = $this->paymentManager->findInvoice(data_get($this->payload, 'data.object.id'), [
            'expand' => ['discounts', 'payments.data.payment.payment_intent'],
        ]);

        if (filled(data_get($this->payload, 'data.object.discounts')) && ($invoice instanceof InvoiceData && filled($invoice->discounts))) {
            $lineItemDiscounts = $lineItems
                ->flatMap(fn (array $item) => Collection::make(data_get($item, 'discount_amounts', [])))
                ->keyBy('discount');

            foreach ($invoice->discounts as $discount) {
                $object = Discount::firstOrCreate([
                    'code' => $discount->code,
                ], [
                    'type' => $discount->type,
                    'discount_type' => $discount->discountType,
                    'value' => $discount->value,
                    'max_uses' => $discount->maxUses,
                    'times_used' => $discount->timesUsed,
                    'expires_at' => $discount->expiresAt,
                    'activated_at' => $discount->activatedAt,
                ]);

                $amountApplied = $lineItemDiscounts->get($discount->externalDiscountId)['amount'] ?? 0;

                $order->discounts()->syncWithPivotValues($object, [
                    'external_discount_id' => $discount->externalDiscountId,
                    'amount_applied' => ((int) $amountApplied) / 100,
                ]);
            }
        }

        if ((blank($order->external_order_id) || blank($order->external_payment_id)) && $invoice instanceof InvoiceData) {
            $order->updateQuietly([
                'external_order_id' => $invoice->externalOrderId,
                'external_payment_id' => $invoice->externalPaymentId,
            ]);
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
            'refund.created' => $this->handleRefundCreated(),
            default => null,
        };
    }

    private function resolveProduct(): ?Product
    {
        return match (data_get($this->payload, 'type')) {
            'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted' => $this->handleSubscriptionEvent(),
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
        // First try: Check invoice metadata for one-off purchases
        $orderId = data_get($this->payload, 'data.object.metadata.order_id');

        if (filled($orderId)) {
            return $orderId;
        }

        // Only search the line items if this is an initial subscription creation invoice
        if (data_get($this->payload, 'data.object.billing_reason') !== 'subscription_create') {
            return null;
        }

        // Second try: Check line items metadata for subscription purchases
        $lineItems = Collection::make(data_get($this->payload, 'data.object.lines.data'));

        $lineItemWithOrderId = $lineItems->first(fn (array $item): bool => filled(data_get($item, 'metadata.order_id')));

        return data_get($lineItemWithOrderId, 'metadata.order_id');
    }

    private function resolveProductId(): ?string
    {
        return data_get($this->payload, 'data.object.plan.product');
    }

    private function resolvePaymentConfirmationUrl(Order $order): ?string
    {
        if (blank($invoiceId = $order->external_invoice_id)) {
            return null;
        }

        if (blank($invoice = $this->paymentManager->findInvoice($invoiceId))) {
            return null;
        }

        return $invoice->invoiceUrl;
    }
}
