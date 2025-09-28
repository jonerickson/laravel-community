<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SupportTicketCreated extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $supportTicket)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Support Ticket Created: '.$this->supportTicket->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-tickets.support-ticket-created',
            with: [
                'supportTicket' => $this->supportTicket,
            ],
        );
    }
}
