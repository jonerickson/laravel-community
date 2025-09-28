<?php

declare(strict_types=1);

namespace App\Mail\Orders;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderProcessing extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Processing - #'.$this->order->reference_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.order-processing',
            with: [
                'order' => $this->order,
            ],
        );
    }
}
