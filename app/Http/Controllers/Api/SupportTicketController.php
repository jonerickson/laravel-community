<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApiResource;
use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController
{
    public function __construct(
        protected readonly SupportTicketManager $supportTicketManager
    ) {
        //
    }

    public function __invoke(Request $request, SupportTicket $ticket): ApiResource
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:support_tickets,id',
            'action' => 'required|string|in:close,resolve,open',
        ]);

        $ticket = SupportTicket::find($validated['ticket_id']);

        /** @var User $user */
        $user = Auth::user();

        abort_unless($ticket->isAuthoredBy($user), 403);

        return match ($validated['action']) {
            'close' => $this->close($ticket),
            'resolve' => $this->resolve($ticket),
            'open' => $this->open($ticket),
        };
    }

    private function close(SupportTicket $ticket): ApiResource
    {
        $result = $this->supportTicketManager->closeTicket($ticket);

        if (! $result) {
            return ApiResource::error(
                message: 'Unable to resolve ticket. Please try again.'
            );
        }

        return ApiResource::success(
            resource: $ticket,
            message: 'The support ticket has been closed.'
        );
    }

    private function resolve(SupportTicket $ticket): ApiResource
    {
        $result = $this->supportTicketManager->resolveTicket($ticket);

        if (! $result) {
            return ApiResource::error(
                message: 'Unable to resolve ticket. Please try again.'
            );
        }

        return ApiResource::success(
            resource: $ticket,
            message: 'The support ticket has been marked as resolved.'
        );
    }

    private function open(SupportTicket $ticket): ApiResource
    {
        $result = $this->supportTicketManager->openTicket($ticket);

        if (! $result) {
            return ApiResource::error(
                message: 'Unable to re-open ticket. Please try again.'
            );
        }

        return ApiResource::success(
            resource: $ticket,
            message: 'The support ticket has been re-opened.'
        );
    }
}
