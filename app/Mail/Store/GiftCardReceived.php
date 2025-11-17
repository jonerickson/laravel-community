<?php

declare(strict_types=1);

namespace App\Mail\Store;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Number;

class GiftCardReceived extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Discount $giftCard,
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Gift Card is Ready!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.store.gift-card-received',
            with: [
                'giftCard' => $this->giftCard,
                'user' => $this->user,
                'code' => $this->giftCard->code,
                'balance' => Number::currency($this->giftCard->current_balance),
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
