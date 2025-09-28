<?php

declare(strict_types=1);

namespace App\Mail\Orders;

use App\Data\InvoiceData;
use App\Managers\PaymentManager;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderSucceeded extends Mailable implements ShouldQueue
{
    use Queueable;

    protected ?InvoiceData $invoice = null;

    public function __construct(public Order $order)
    {
        $this->invoice = app(PaymentManager::class)->findInvoice($order);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Succeeded - #'.$this->order->reference_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.order-succeeded',
            with: [
                'order' => $this->order,
            ],
        );
    }

    public function attachments(): array
    {
        if (blank($url = $this->invoice->invoicePdfUrl)) {
            return [];
        }

        return [
            Attachment::fromUrl($url),
        ];
    }
}
