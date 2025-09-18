<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTickets\StoreSupportTicketCommentRequest;
use App\Managers\SupportTicketManager;
use App\Models\Comment;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function __construct(
        protected readonly SupportTicketManager $supportTicketManager
    ) {
        //
    }

    public function store(StoreSupportTicketCommentRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validated();

        $this->supportTicketManager->addComment(
            ticket: $ticket,
            content: $validated['content'],
            userId: Auth::id(),
        );

        return to_route('support.show', $ticket)
            ->with('message', 'Your reply was successfully created.');
    }

    public function destroy(SupportTicket $ticket, Comment $comment): RedirectResponse
    {
        abort_unless($ticket->isAuthoredBy(Auth::user()), 403);
        abort_unless($comment->created_by === Auth::id(), 403);

        $this->supportTicketManager->deleteComment($ticket, $comment);

        return to_route('support.show', $ticket)
            ->with('message', 'The reply was successfully deleted.');
    }
}
