<?php

declare(strict_types=1);

namespace App\Mail\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SubscriptionUpdated extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public ?SubscriptionStatus $newStatus = null
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Updated - #'.$this->order->reference_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscriptions.subscription-updated',
            with: [
                'order' => $this->order,
                'newStatus' => $this->newStatus,
                'oldStatus' => $this->order->getOriginal('subscription_status'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
