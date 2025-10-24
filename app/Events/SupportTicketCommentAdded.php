<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;
use App\Models\SupportTicket;

class SupportTicketCommentAdded
{
    public function __construct(public SupportTicket $supportTicket, public Comment $comment)
    {
        //
    }
}
