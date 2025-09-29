<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\PaymentActionRequired;
use App\Events\PaymentSucceeded;
use App\Events\RefundCreated;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionDeleted;
use App\Events\SubscriptionUpdated;
use App\Mail\Payments\PaymentActionRequired as PaymentActionRequiredMail;
use App\Mail\Payments\PaymentSucceeded as PaymentSucceededMail;
use App\Mail\Payments\RefundCreated as RefundCreatedMail;
use Illuminate\Support\Facades\Mail;

class HandlePaymentProcessorEvent
{
    public function handle(PaymentSucceeded|PaymentActionRequired|SubscriptionCreated|SubscriptionUpdated|SubscriptionDeleted|RefundCreated $event): void
    {
        match ($event::class) {
            PaymentSucceeded::class => $this->handlePaymentSucceeded($event),
            PaymentActionRequired::class => $this->handlePaymentActionRequired($event),
            SubscriptionCreated::class => $this->handleSubscriptionCreated(),
            SubscriptionUpdated::class => $this->handleSubscriptionUpdated(),
            SubscriptionDeleted::class => $this->handleSubscriptionDeleted(),
            RefundCreated::class => $this->handleRefundCreated($event),
        };
    }

    private function handlePaymentSucceeded(PaymentSucceeded $event): void
    {
        if ($event->order->user) {
            Mail::to($event->order->user->email)->send(new PaymentSucceededMail($event->order));
        }

        if ($event->order->status === OrderStatus::Succeeded) {
            return;
        }

        $event->order->update([
            'status' => OrderStatus::Succeeded,
        ]);
    }

    private function handlePaymentActionRequired(PaymentActionRequired $event): void
    {
        if ($event->order->user && $event->confirmationUrl !== null) {
            Mail::to($event->order->user->email)->send(new PaymentActionRequiredMail($event->order, $event->confirmationUrl));
        }

        if ($event->order->status === OrderStatus::RequiresAction) {
            return;
        }

        $event->order->update([
            'status' => OrderStatus::RequiresAction,
        ]);
    }

    private function handleSubscriptionCreated(): void
    {
        //
    }

    private function handleSubscriptionUpdated(): void
    {
        //
    }

    private function handleSubscriptionDeleted(): void
    {
        //
    }

    private function handleRefundCreated(RefundCreated $event): void
    {
        if ($event->order->user) {
            Mail::to($event->order->user->email)->send(new RefundCreatedMail($event->order, $event->reason));
        }

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
