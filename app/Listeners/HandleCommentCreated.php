<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Events\SupportTicketCommentAdded;
use App\Models\SupportTicket;

class HandleCommentCreated
{
    public function handle(CommentCreated $event): void
    {
        if ($event->comment->commentable_type === SupportTicket::class) {
            /** @var SupportTicket $supportTicket */
            $supportTicket = $event->comment->commentable;

            SupportTicketCommentAdded::dispatch($supportTicket, $event->comment);
        }
    }
}
