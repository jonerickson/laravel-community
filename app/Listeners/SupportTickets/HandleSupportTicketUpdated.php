<?php

declare(strict_types=1);

namespace App\Listeners\SupportTickets;

use App\Events\SupportTicketStatusChanged;
use App\Events\SupportTicketUpdated;
use Illuminate\Support\Facades\App;

class HandleSupportTicketUpdated
{
    public function handle(SupportTicketUpdated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event->supportTicket->wasChanged('status')) {
            event(new SupportTicketStatusChanged(
                supportTicket: $event->supportTicket,
                oldStatus: $event->supportTicket->getOriginal('status'),
                newStatus: $event->supportTicket->status
            ));
        }
    }
}
