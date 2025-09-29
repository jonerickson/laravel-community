<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SupportTicketStatusChanged implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $supportTicket,
        public SupportTicketStatus $oldStatus,
        public SupportTicketStatus $newStatus
    ) {
        //
    }
}
