<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\PaymentActionRequired;
use App\Events\PaymentSucceeded;
use App\Events\RefundCreated;

class HandlePaymentProcessorEvent
{
    public function handle(PaymentSucceeded|PaymentActionRequired|RefundCreated $event): void
    {
        match ($event::class) {
            PaymentSucceeded::class => $this->handlePaymentSucceeded($event),
            PaymentActionRequired::class => $this->handlePaymentActionRequired($event),
            RefundCreated::class => $this->handleRefundCreated($event),

        };
    }

    private function handlePaymentSucceeded(PaymentSucceeded $event): void
    {
        if ($event->order->status === OrderStatus::Succeeded) {
            return;
        }

        $event->order->update([
            'status' => OrderStatus::Succeeded,
        ]);
    }

    private function handlePaymentActionRequired(PaymentActionRequired $event): void
    {
        if ($event->order->status === OrderStatus::RequiresAction) {
            return;
        }

        $event->order->update([
            'status' => OrderStatus::RequiresAction,
        ]);
    }

    private function handleRefundCreated(RefundCreated $event): void
    {
        if ($event->order->status === OrderStatus::Refunded) {
            return;
        }

        $event->order->update([
            'status' => OrderStatus::Refunded,
            'refund_reason' => $event->reason,
            'refund_notes' => $event->notes,
        ]);
    }
}
