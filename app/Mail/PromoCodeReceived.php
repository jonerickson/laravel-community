<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Number;

class PromoCodeReceived extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Discount $promoCode,
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Promo Code is Ready!',
        );
    }

    public function content(): Content
    {
        $discountValue = $this->promoCode->discount_type === DiscountValueType::Percentage
            ? $this->promoCode->value.'%'
            : Number::currency($this->promoCode->value / 100);

        return new Content(
            markdown: 'emails.promo-code-received',
            with: [
                'promoCode' => $this->promoCode,
                'user' => $this->user,
                'code' => $this->promoCode->code,
                'discountValue' => $discountValue,
                'expiresAt' => $this->promoCode->expires_at?->format('F j, Y'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
