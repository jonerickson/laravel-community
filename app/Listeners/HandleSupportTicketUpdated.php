<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SupportTicketStatusChanged;
use App\Events\SupportTicketUpdated;

class HandleSupportTicketUpdated
{
    public function handle(SupportTicketUpdated $event): void
    {
        if ($event->supportTicket->isDirty('status')) {
            event(new SupportTicketStatusChanged(
                supportTicket: $event->supportTicket,
                oldStatus: $event->supportTicket->getOriginal('status'),
                newStatus: $event->supportTicket->status
            ));
        }
    }
}
