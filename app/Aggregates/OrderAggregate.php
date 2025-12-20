<?php

declare(strict_types=1);

namespace App\Aggregates;

use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderPending;
use App\Events\OrderProcessing;
use App\Events\OrderRefunded;
use App\Events\OrderSaved;
use App\Events\OrderSucceeded;
use App\Models\Order;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class OrderAggregate extends AggregateRoot
{
    private OrderStatus $status;

    private ?float $amountPaid = null;

    private ?float $amountRemaining = null;

    private ?float $amountDue = null;

    public function recordOrderCreated(Order $order): self
    {
        $this->recordThat(new OrderCreated($order));

        return $this;
    }

    public function recordOrderSaved(Order $order): self
    {
        $this->recordThat(new OrderSaved($order));

        return $this;
    }

    public function recordOrderSucceeded(Order $order): self
    {
        $this->recordThat(new OrderSucceeded($order));

        return $this;
    }

    public function recordOrderRefunded(Order $order): self
    {
        $this->recordThat(new OrderRefunded($order));

        return $this;
    }

    public function recordOrderCancelled(Order $order): self
    {
        $this->recordThat(new OrderCancelled($order));

        return $this;
    }

    public function recordOrderPending(Order $order): self
    {
        $this->recordThat(new OrderPending($order));

        return $this;
    }

    public function recordOrderProcessing(Order $order): self
    {
        $this->recordThat(new OrderProcessing($order));

        return $this;
    }

    protected function applyOrderCreated(OrderCreated $event): void
    {
        $this->status = $event->order->status;
        $this->amountDue = $event->order->amount_due;
        $this->amountPaid = $event->order->amount_paid;
        $this->amountRemaining = $event->order->amount_remaining;
    }

    protected function applyOrderSaved(OrderSaved $event): void
    {
        $this->status = $event->order->status;
        $this->amountPaid = $event->order->amount_paid;
        $this->amountRemaining = $event->order->amount_remaining;
        $this->amountDue = $event->order->amount_due;
    }

    protected function applyOrderSucceeded(OrderSucceeded $event): void
    {
        $this->status = OrderStatus::Succeeded;
    }

    protected function applyOrderRefunded(OrderRefunded $event): void
    {
        $this->status = OrderStatus::Refunded;
    }

    protected function applyOrderCancelled(OrderCancelled $event): void
    {
        $this->status = OrderStatus::Cancelled;
    }

    protected function applyOrderPending(OrderPending $event): void
    {
        $this->status = OrderStatus::Pending;
    }

    protected function applyOrderProcessing(OrderProcessing $event): void
    {
        $this->status = OrderStatus::Processing;
    }
}