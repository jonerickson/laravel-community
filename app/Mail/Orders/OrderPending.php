<?php

declare(strict_types=1);

namespace App\Mail\Orders;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderPending extends Mailable implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Order $order)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Pending - #'.$this->order->reference_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.order-pending',
            with: [
                'order' => $this->order,
            ],
        );
    }
}
