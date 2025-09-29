<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderPending;
use App\Events\OrderProcessing;
use App\Events\OrderSucceeded;
use App\Mail\Orders\OrderCancelled as OrderCancelledMail;
use App\Mail\Orders\OrderPending as OrderPendingMail;
use App\Mail\Orders\OrderProcessing as OrderProcessingMail;
use App\Mail\Orders\OrderSucceeded as OrderSucceededMail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class HandleOrderEvent
{
    public function handle(OrderCancelled|OrderPending|OrderProcessing|OrderSucceeded $event): void
    {
        match ($event::class) {
            OrderCancelled::class => $this->sendMail(
                new OrderCancelledMail($event->order),
                $event->order
            ),
            OrderPending::class => $this->sendMail(
                new OrderPendingMail($event->order),
                $event->order
            ),
            OrderProcessing::class => $this->sendMail(
                new OrderProcessingMail($event->order),
                $event->order
            ),
            OrderSucceeded::class => $this->sendMail(
                new OrderSucceededMail($event->order),
                $event->order
            ),
            default => null,
        };
    }

    protected function sendMail(Mailable $mailable, $order): void
    {
        if ($order->user) {
            Mail::to($order->user->email)->send($mailable);
        }
    }
}
