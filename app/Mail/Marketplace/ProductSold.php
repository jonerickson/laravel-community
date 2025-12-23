<?php

declare(strict_types=1);

namespace App\Mail\Marketplace;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

class ProductSold extends Mailable implements ShouldQueue
{
    use Queueable;

    public Collection $items;

    public function __construct(
        public Order $order,
        public User $seller,
    ) {
        $this->items = $this->order->items()->whereRelation('price.product', 'seller_id', $this->seller->id)->get();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You Made A Sale! - Order #'.$this->order->reference_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.marketplace.product-sold',
            with: [
                'order' => $this->order,
                'seller' => $this->seller,
                'items' => $this->items,
            ],
        );
    }
}
