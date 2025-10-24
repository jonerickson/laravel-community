<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;

class SupportTicketStatusChanged
{
    public function __construct(
        public SupportTicket $supportTicket,
        public SupportTicketStatus $oldStatus,
        public SupportTicketStatus $newStatus
    ) {
        //
    }
}
