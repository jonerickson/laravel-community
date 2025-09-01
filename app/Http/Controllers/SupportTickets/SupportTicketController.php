<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTickets\StoreSupportTicketRequest;
use App\Managers\SupportTicketManager;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
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
        $tickets = SupportTicket::with(['category', 'author', 'assignedTo'])
            ->whereBelongsTo(Auth::user(), 'author')
            ->latest()
            ->paginate(15);

        return Inertia::render('support/index', [
            'tickets' => Inertia::merge(fn () => $tickets->items()),
            'ticketsPagination' => Arr::except($tickets->toArray(), ['data']),
        ]);
    }

    public function create(): Response
    {
        $categories = SupportTicketCategory::active()->ordered()->get();

        return Inertia::render('support/create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $ticket = $this->supportTicketManager->createTicket($validated);

        return to_route('support.show', $ticket)
            ->with('message', 'Support ticket created successfully!');
    }

    public function show(SupportTicket $ticket): Response
    {
        abort_unless($ticket->isAuthoredBy(Auth::user()), 403);

        $ticket->loadMissing(['category', 'author', 'assignedTo', 'comments.author', 'files']);

        return Inertia::render('support/show', [
            'ticket' => $ticket,
        ]);
    }
}
