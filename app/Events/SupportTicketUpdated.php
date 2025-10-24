<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SupportTicket;

class SupportTicketUpdated
{
    public function __construct(
        public SupportTicket $supportTicket,
    ) {
        //
    }
}
