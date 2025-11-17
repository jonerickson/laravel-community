<?php

declare(strict_types=1);

namespace App\Mail\Payments;

use App\Enums\OrderRefundReason;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RefundCreated extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public OrderRefundReason $reason,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Refund Processed - #'.$this->order->reference_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payments.refund-created',
            with: [
                'order' => $this->order,
                'reason' => $this->reason,
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
