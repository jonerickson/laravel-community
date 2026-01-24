<?php

declare(strict_types=1);

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;

it('can create support ticket', function (): void {
    $category = SupportTicketCategory::factory()->create();
    $user = User::factory()->create();

    $ticket = SupportTicket::factory()->create([
        'support_ticket_category_id' => $category->id,
        'created_by' => $user->id,
    ]);

    expect($ticket)->toBeInstanceOf(SupportTicket::class)
        ->and($ticket->ticket_number)->not()->toBeNull()
        ->and($ticket->ticket_number)->toStartWith('ST-');

    $this->assertDatabaseHas('support_tickets', [
        'id' => $ticket->id,
        'support_ticket_category_id' => $category->id,
        'created_by' => $user->id,
    ]);
});

it('has default status and priority', function (): void {
    $ticket = SupportTicket::factory()->create([
        'status' => null,
        'priority' => null,
    ]);

    expect($ticket->fresh()->statusEnum())->toBe(SupportTicketStatus::New)
        ->and($ticket->fresh()->priorityEnum())->toBe(SupportTicketPriority::Medium);
});

it('can assign ticket to user', function (): void {
    $ticket = SupportTicket::factory()->unassigned()->create();
    $assignee = User::factory()->create();

    $result = $ticket->assign($assignee);

    expect($result)->toBeTrue()
        ->and($ticket->isAssignedTo($assignee))->toBeTrue()
        ->and($ticket->fresh()->assigned_to)->toBe($assignee->id);
});

it('can unassign ticket', function (): void {
    $ticket = SupportTicket::factory()->assigned()->create();

    $result = $ticket->unassign();

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->assigned_to)->toBeNull();
});

it('can transition ticket status', function (): void {
    $ticket = SupportTicket::factory()->new()->create();

    expect($ticket->canTransitionTo(SupportTicketStatus::Open))->toBeTrue();

    $result = $ticket->updateStatus(SupportTicketStatus::Open);

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->statusEnum())->toBe(SupportTicketStatus::Open);
});

it('cannot transition to invalid status', function (): void {
    $ticket = SupportTicket::factory()->closed()->create();

    expect($ticket->canTransitionTo(SupportTicketStatus::New))->toBeFalse();

    $result = $ticket->updateStatus(SupportTicketStatus::New);

    expect($result)->toBeFalse();
});

it('belongs to category', function (): void {
    $category = SupportTicketCategory::factory()->create();
    $ticket = SupportTicket::factory()->create([
        'support_ticket_category_id' => $category->id,
    ]);

    expect($ticket->category)->toBeInstanceOf(SupportTicketCategory::class)
        ->and($ticket->category->id)->toBe($category->id);
});

it('belongs to author', function (): void {
    $user = User::factory()->create();
    $ticket = SupportTicket::factory()->create([
        'created_by' => $user->id,
    ]);

    expect($ticket->author)->toBeInstanceOf(User::class)
        ->and($ticket->author->id)->toBe($user->id);
});

it('can be assigned to user', function (): void {
    $assignee = User::factory()->create();
    $ticket = SupportTicket::factory()->create([
        'assigned_to' => $assignee->id,
    ]);

    expect($ticket->assignedTo)->toBeInstanceOf(User::class)
        ->and($ticket->assignedTo->id)->toBe($assignee->id);
});

it('has working scopes', function (): void {
    SupportTicket::factory()->new()->count(3)->create();
    SupportTicket::factory()->open()->count(2)->create();
    SupportTicket::factory()->closed()->count(1)->create();

    $activeTickets = SupportTicket::active()->get();
    $newTickets = SupportTicket::byStatus(SupportTicketStatus::New)->get();

    expect($activeTickets)->toHaveCount(5)
        ->and($newTickets)->toHaveCount(3);
});

it('handles external ticket methods', function (): void {
    $externalTicket = SupportTicket::factory()->external()->create();
    $localTicket = SupportTicket::factory()->create();

    expect($externalTicket->isExternal())->toBeTrue()
        ->and($localTicket->isExternal())->toBeFalse();

    $result = $externalTicket->markSynced();

    expect($result)->toBeTrue()
        ->and($externalTicket->fresh()->last_synced_at)->not()->toBeNull();
});

it('generates unique ticket numbers', function (): void {
    $ticket1 = SupportTicket::factory()->create();
    $ticket2 = SupportTicket::factory()->create();

    expect($ticket1->ticket_number)->not()->toBe($ticket2->ticket_number);
});

it('has correct attributes', function (): void {
    $ticket = SupportTicket::factory()->highPriority()->open()->create();

    expect($ticket->priority_label)->toBe('High')
        ->and($ticket->status_label)->toBe('Open')
        ->and($ticket->priority_color)->not()->toBeNull()
        ->and($ticket->status_color)->not()->toBeNull()
        ->and($ticket->is_active)->toBeTrue();
});
