<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;
use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SupportTicketCommentAdded implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $supportTicket, public Comment $comment)
    {
        //
    }
}
