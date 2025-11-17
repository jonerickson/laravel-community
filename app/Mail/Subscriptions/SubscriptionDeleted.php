<?php

declare(strict_types=1);

namespace App\Mail\Subscriptions;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SubscriptionDeleted extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public Product $product
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Cancelled - '.$this->product->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscriptions.subscription-deleted',
            with: [
                'user' => $this->user,
                'product' => $this->product,
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
