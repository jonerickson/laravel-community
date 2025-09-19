<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Data\PaginatedData;
use App\Data\SupportTicketCategoryData;
use App\Data\SupportTicketData;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTickets\StoreSupportTicketRequest;
use App\Http\Requests\SupportTickets\UpdateSupportTicketRequest;
use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketManager $supportTicketManager
    ) {}

    public function index(): Response
    {
        $tickets = SupportTicket::query()
            ->with('category')
            ->with('author')
            ->with('assignedTo')
            ->whereBelongsTo(Auth::user(), 'author')
            ->latest()
            ->paginate(15);

        return Inertia::render('support/index', [
            'tickets' => Inertia::merge(fn () => SupportTicketData::collect($tickets->items())),
            'ticketsPagination' => PaginatedData::from(Arr::except($tickets->toArray(), ['data'])),
        ]);
    }

    public function create(): Response
    {
        $categories = SupportTicketCategory::active()->ordered()->get();

        return Inertia::render('support/create', [
            'categories' => SupportTicketCategoryData::collect($categories),
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ticket = $this->supportTicketManager->createTicket($validated);

        return to_route('support.show', $ticket)
            ->with('message', 'Your support ticket was successfully created. Please check your email for updates.');
    }

    public function show(SupportTicket $ticket): Response
    {
        abort_unless($ticket->isAuthoredBy(Auth::user()), 403);

        $ticket->loadMissing(['category', 'author', 'assignedTo', 'comments.author', 'files']);

        return Inertia::render('support/show', [
            'ticket' => SupportTicketData::from($ticket),
        ]);
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validated();

        /** @var User $user */
        $user = Auth::user();

        abort_unless($ticket->isAuthoredBy($user), 403);

        $result = match ($validated['action']) {
            'close' => $this->closeTicket($ticket),
            'resolve' => $this->resolveTicket($ticket),
            'open' => $this->openTicket($ticket),
        };

        if (! $result['success']) {
            return back()->with([
                'message' => $result['message'],
                'messageVariant' => 'error',
            ]);
        }

        return back()->with([
            'message' => $result['message'],
            'messageVariant' => 'success',
        ]);
    }

    private function closeTicket(SupportTicket $ticket): array
    {
        $result = $this->supportTicketManager->closeTicket($ticket);

        if (! $result) {
            return [
                'success' => false,
                'message' => 'Unable to close ticket. Please try again.',
            ];
        }

        return [
            'success' => true,
            'message' => 'The support ticket has been closed.',
        ];
    }

    private function resolveTicket(SupportTicket $ticket): array
    {
        $result = $this->supportTicketManager->resolveTicket($ticket);

        if (! $result) {
            return [
                'success' => false,
                'message' => 'Unable to resolve ticket. Please try again.',
            ];
        }

        return [
            'success' => true,
            'message' => 'The support ticket has been marked as resolved.',
        ];
    }

    private function openTicket(SupportTicket $ticket): array
    {
        $result = $this->supportTicketManager->openTicket($ticket);

        if (! $result) {
            return [
                'success' => false,
                'message' => 'Unable to re-open ticket. Please try again.',
            ];
        }

        return [
            'success' => true,
            'message' => 'The support ticket has been re-opened.',
        ];
    }
}
