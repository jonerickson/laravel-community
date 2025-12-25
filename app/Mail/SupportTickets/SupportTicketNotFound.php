<?php

declare(strict_types=1);

namespace App\Mail\SupportTickets;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SupportTicketNotFound extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $ticketNumber)
    {
        if ($inboundMailbox = config('mailbox.mailboxes.support')) {
            $this->replyTo($inboundMailbox);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Support Ticket Not Found: '.$this->ticketNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-tickets.support-ticket-not-found',
            with: [
                'ticketNumber' => $this->ticketNumber,
            ],
        );
    }
}
