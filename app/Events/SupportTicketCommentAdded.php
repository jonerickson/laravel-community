<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;
use App\Models\SupportTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportTicketCommentAdded implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public function __construct(public SupportTicket $supportTicket, public Comment $comment)
    {
        //
    }
}
