<?php

declare(strict_types=1);

namespace App\Mail\Orders;

use App\Data\InvoiceData;
use App\Managers\PaymentManager;
use App\Models\Order;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;

class OrderSucceeded extends Mailable implements ShouldQueue
{
    use Queueable;

    protected ?InvoiceData $invoice = null;

    public function __construct(public Order $order)
    {
        if ($invoiceId = $this->order->external_invoice_id) {
            $this->invoice = app(PaymentManager::class)->findInvoice($invoiceId);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Successful - #'.$this->order->reference_id,
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
        if (! $this->invoice instanceof InvoiceData) {
            return [];
        }

        if (blank($url = $this->invoice->invoicePdfUrl)) {
            return [];
        }

        $filename = sprintf('invoices/%s.pdf', $this->order->reference_id);

        try {
            $pdfContents = file_get_contents($url);
        } catch (Exception) {
            return [];
        }

        if ($pdfContents === false) {
            return [];
        }

        $result = Storage::put($filename, $pdfContents);

        if (! $result) {
            return [];
        }

        return [
            Attachment::fromStorage($filename)
                ->as($this->order->reference_id.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
