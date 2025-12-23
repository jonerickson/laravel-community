<?php

declare(strict_types=1);

namespace App\Mail\Payouts;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewPayout extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(protected Payout $payout)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've Been Paid!",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payouts.new-payout',
            with: [
                'payout' => $this->payout,
            ]
        );
    }
}
