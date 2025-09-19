<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTickets\StoreSupportTicketCommentRequest;
use App\Managers\SupportTicketManager;
use App\Models\Comment;
use App\Models\SupportTicket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected readonly SupportTicketManager $supportTicketManager
    ) {
        //
    }

    public function store(StoreSupportTicketCommentRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $this->authorize('create', Comment::class);

        $validated = $request->validated();

        $this->supportTicketManager->addComment(
            ticket: $ticket,
            content: $validated['content'],
            userId: Auth::id(),
        );

        return to_route('support.show', $ticket)
            ->with('message', 'Your reply was successfully added.');
    }

    public function destroy(SupportTicket $ticket, Comment $comment): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $this->authorize('delete', $comment);

        $this->supportTicketManager->deleteComment($ticket, $comment);

        return to_route('support.show', $ticket)
            ->with('message', 'The reply was successfully deleted.');
    }
}
