<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SupportTicketCommentAdded;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketStatusChanged;
use App\Events\SupportTicketUpdated;
use App\Mail\SupportTickets\SupportTicketCommentAdded as SupportTicketCommentAddedMail;
use App\Mail\SupportTickets\SupportTicketCreated as SupportTicketCreatedMail;
use App\Mail\SupportTickets\SupportTicketStatusChanged as SupportTicketStatusChangedMail;
use Illuminate\Support\Facades\Mail;

class HandleSupportTicketEvent
{
    public function handle(SupportTicketCreated|SupportTicketCommentAdded|SupportTicketStatusChanged|SupportTicketUpdated $event): void
    {
        match ($event::class) {
            SupportTicketCreated::class => $this->sendMail(
                new SupportTicketCreatedMail($event->supportTicket),
                $event->supportTicket
            ),
            SupportTicketCommentAdded::class => $this->sendMail(
                new SupportTicketCommentAddedMail($event->supportTicket, $event->comment),
                $event->supportTicket
            ),
            SupportTicketStatusChanged::class => $this->sendMail(
                new SupportTicketStatusChangedMail($event->supportTicket, $event->oldStatus, $event->newStatus),
                $event->supportTicket
            ),
            default => null,
        };
    }

    protected function sendMail(\Illuminate\Contracts\Mail\Mailable $mailable, $supportTicket): void
    {
        if ($supportTicket->author) {
            Mail::to($supportTicket->author->email)->send($mailable);
        }

        if ($supportTicket->assignedTo && $supportTicket->assignedTo->id !== $supportTicket->created_by) {
            Mail::to($supportTicket->assignedTo->email)->send($mailable);
        }
    }
}
