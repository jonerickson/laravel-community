<?php

declare(strict_types=1);

namespace App\Mail\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SubscriptionUpdated extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public Product $product,
        public ?SubscriptionStatus $newStatus = null,
        public ?SubscriptionStatus $oldStatus = null
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Updated - '.$this->product->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscriptions.subscription-updated',
            with: [
                'user' => $this->user,
                'product' => $this->product,
                'newStatus' => $this->newStatus,
                'oldStatus' => $this->oldStatus,
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
