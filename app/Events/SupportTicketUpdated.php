<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SupportTicketUpdated implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $supportTicket,
    ) {
        //
    }
}
