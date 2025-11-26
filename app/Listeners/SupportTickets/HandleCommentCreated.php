<?php

declare(strict_types=1);

namespace App\Listeners\SupportTickets;

use App\Events\CommentCreated;
use App\Events\SupportTicketCommentAdded;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\App;

class HandleCommentCreated
{
    public function handle(CommentCreated $event): void
    {
        if (App::runningConsoleCommand('app:migrate')) {
            return;
        }

        if ($event->comment->commentable_type === SupportTicket::class) {
            /** @var SupportTicket $supportTicket */
            $supportTicket = $event->comment->commentable;

            event(new SupportTicketCommentAdded(
                supportTicket: $supportTicket,
                comment: $event->comment
            ));
        }
    }
}
