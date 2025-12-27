<?php

declare(strict_types=1);

namespace App\Http\Controllers\SupportTickets;

use App\Data\OrderData;
use App\Data\PaginatedData;
use App\Data\SupportTicketCategoryData;
use App\Data\SupportTicketData;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTickets\StoreSupportTicketRequest;
use App\Http\Requests\SupportTickets\UpdateSupportTicketRequest;
use App\Managers\SupportTicketManager;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class SupportTicketController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SupportTicketManager $supportTicketManager,
        #[CurrentUser]
        private readonly ?User $user = null,
    ) {
        //
    }

    public function index(): Response|RedirectResponse
    {
        if (! $this->user instanceof User) {
            return redirect()->route('knowledge-base.index');
        }

        $this->authorize('viewAny', SupportTicket::class);

        $tickets = SupportTicket::query()
            ->with(['category', 'author', 'latestComment.author'])
            ->whereBelongsTo($this->user, 'author')
            ->latest()
            ->paginate();

        $filteredTickets = $tickets
            ->collect()
            ->filter(fn (SupportTicket $ticket) => Gate::check('view', $ticket))
            ->values();

        return Inertia::render('support/index', [
            'tickets' => PaginatedData::from(SupportTicketData::collect($tickets->setCollection($filteredTickets), PaginatedDataCollection::class)->items()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SupportTicket::class);

        $categories = SupportTicketCategory::active()->ordered()->get();

        $orders = Order::query()
            ->whereBelongsTo($this->user)
            ->latest()
            ->get();

        return Inertia::render('support/create', [
            'categories' => SupportTicketCategoryData::collect($categories),
            'orders' => OrderData::collect($orders),
        ]);
    }

    public function store(StoreSupportTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', SupportTicket::class);

        $validated = $request->validated();

        $ticket = $this->supportTicketManager->createTicket($validated);

        return to_route('support.show', $ticket->reference_id)
            ->with('message', 'Your support ticket was successfully created. Please check your email for updates.');
    }

    public function show(SupportTicket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->loadMissing(['comments.author.groups', 'files', 'author.groups', 'category']);

        return Inertia::render('support/show', [
            'ticket' => SupportTicketData::from($ticket),
        ]);
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $result = match ($request->validated('action')) {
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
