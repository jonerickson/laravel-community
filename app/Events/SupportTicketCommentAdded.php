<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;
use App\Models\SupportTicket;
use Illuminate\Foundation\Queue\Queueable;

class SupportTicketCommentAdded
{
    use Queueable;

    public function __construct(public SupportTicket $supportTicket, public Comment $comment)
    {
        //
    }
}
