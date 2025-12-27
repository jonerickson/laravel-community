<?php

declare(strict_types=1);

namespace App\Mail\SupportTickets;

use App\Models\Comment;
use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SupportTicketCommentAdded extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $supportTicket,
        public Comment $comment
    ) {
        if ($inboundMailbox = config('mailbox.mailboxes.support')) {
            $this->replyTo($inboundMailbox);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Reply on Support Ticket: '.$this->supportTicket->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support-tickets.support-ticket-comment-added',
            with: [
                'supportTicket' => $this->supportTicket,
                'comment' => $this->comment,
            ],
        );
    }
}
