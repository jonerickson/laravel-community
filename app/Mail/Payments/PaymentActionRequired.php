<?php

declare(strict_types=1);

namespace App\Mail\Payments;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentActionRequired extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $confirmationUrl
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Action Required - #'.$this->order->reference_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payments.payment-action-required',
            with: [
                'order' => $this->order,
                'confirmationUrl' => $this->confirmationUrl,
            ],
        );
    }

    /**
     * @return array{}
     */
    public function attachments(): array
    {
        return [];
    }
}
