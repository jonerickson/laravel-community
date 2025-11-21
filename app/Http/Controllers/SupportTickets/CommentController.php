<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTickets\StoreSupportTicketCommentRequest;
use App\Managers\SupportTicketManager;
use App\Models\Comment;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        #[CurrentUser]
        private readonly User $user,
        private readonly SupportTicketManager $supportTicketManager
    ) {
        //
    }

    public function store(StoreSupportTicketCommentRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $this->authorize('create', Comment::class);

        $this->supportTicketManager->addComment(
            ticket: $ticket,
            content: $request->validated('content'),
            userId: $this->user->id,
        );

        return back()->with('message', 'Your reply was successfully added.');
    }

    public function destroy(SupportTicket $ticket, Comment $comment): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $this->authorize('delete', $comment);

        $this->supportTicketManager->deleteComment($ticket, $comment);

        return back()->with('message', 'The reply was successfully deleted.');
    }
}
