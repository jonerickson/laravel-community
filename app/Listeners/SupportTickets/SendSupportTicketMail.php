<?php

declare(strict_types=1);

namespace App\Listeners\SupportTickets;

use App\Events\SupportTicketCommentAdded;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketStatusChanged;
use App\Events\SupportTicketUpdated;
use App\Mail\SupportTickets\SupportTicketCommentAdded as SupportTicketCommentAddedMail;
use App\Mail\SupportTickets\SupportTicketCreated as SupportTicketCreatedMail;
use App\Mail\SupportTickets\SupportTicketStatusChanged as SupportTicketStatusChangedMail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class SendSupportTicketMail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;

    public function handle(SupportTicketCreated|SupportTicketCommentAdded|SupportTicketStatusChanged|SupportTicketUpdated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        match ($event::class) {
            SupportTicketCreated::class => $this->sendMail(
                mailable: new SupportTicketCreatedMail($event->supportTicket),
                supportTicket: $event->supportTicket
            ),
            SupportTicketCommentAdded::class => $this->sendMail(
                mailable: new SupportTicketCommentAddedMail($event->supportTicket, $event->comment),
                supportTicket: $event->supportTicket
            ),
            SupportTicketStatusChanged::class => $this->sendMail(
                mailable: new SupportTicketStatusChangedMail($event->supportTicket, $event->oldStatus, $event->newStatus),
                supportTicket: $event->supportTicket
            ),
            default => null,
        };
    }

    protected function sendMail(Mailable $mailable, $supportTicket): void
    {
        if ($supportTicket->author) {
            Mail::to($supportTicket->author->email)->send($mailable);
        }

        if ($supportTicket->assignedTo && $supportTicket->assignedTo->id !== $supportTicket->created_by) {
            Mail::to($supportTicket->assignedTo->email)->send($mailable);
        }
    }
}
