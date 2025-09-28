<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Foundation\Queue\Queueable;

class SupportTicketUpdated
{
    use Queueable;

    public function __construct(
        public SupportTicket $supportTicket,
    ) {
        //
    }
}
