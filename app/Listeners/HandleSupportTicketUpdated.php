<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SupportTicketStatusChanged;
use App\Events\SupportTicketUpdated;

class HandleSupportTicketUpdated
{
    public function handle(SupportTicketUpdated $event): void
    {
        if ($event->supportTicket->wasChanged('status')) {
            SupportTicketStatusChanged::dispatch($event->supportTicket, $event->supportTicket->getOriginal('status'), $event->supportTicket->status);
        }
    }
}
