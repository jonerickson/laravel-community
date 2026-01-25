<?php

declare(strict_types=1);

use App\Drivers\SupportTickets\DatabaseDriver;
use App\Drivers\SupportTickets\SupportTicketProvider;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;

beforeEach(function (): void {
    $this->driver = app(SupportTicketProvider::class);
});

it('can create ticket through driver', function (): void {
    $category = SupportTicketCategory::factory()->create();
    $user = User::factory()->create();

    $data = [
        'subject' => 'Test Ticket',
        'description' => 'This is a test ticket',
        'support_ticket_category_id' => $category->id,
        'created_by' => $user->id,
    ];

    $ticket = $this->driver->createTicket($data);

    expect($ticket)->toBeInstanceOf(SupportTicket::class)
        ->and($ticket->subject)->toBe('Test Ticket');

    $this->assertDatabaseHas('support_tickets', ['subject' => 'Test Ticket']);
});

it('can update ticket through driver', function (): void {
    $ticket = SupportTicket::factory()->create();

    $result = $this->driver->updateTicket($ticket, [
        'subject' => 'Updated Subject',
    ]);

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->subject)->toBe('Updated Subject');
});

it('can delete ticket through driver', function (): void {
    $ticket = SupportTicket::factory()->create();

    $result = $this->driver->deleteTicket($ticket);

    expect($result)->toBeTrue();

    $this->assertDatabaseMissing('support_tickets', ['id' => $ticket->id]);
});

it('can add comment through driver', function (): void {
    $ticket = SupportTicket::factory()->create();
    $user = User::factory()->create();

    $result = $this->driver->addComment($ticket, 'This is a comment', $user->id);

    expect($result)->toBeTrue();

    $this->assertDatabaseHas('comments', [
        'commentable_type' => SupportTicket::class,
        'commentable_id' => $ticket->id,
        'content' => 'This is a comment',
        'created_by' => $user->id,
    ]);
});

it('can assign ticket through driver', function (): void {
    $ticket = SupportTicket::factory()->unassigned()->create();
    $user = User::factory()->create();

    $result = $this->driver->assignTicket($ticket, (string) $user->id);

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->assigned_to)->toBe($user->id);
});

it('can unassign ticket through driver', function (): void {
    $ticket = SupportTicket::factory()->assigned()->create();

    $result = $this->driver->assignTicket($ticket, null);

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->assigned_to)->toBeNull();
});

it('can update status through driver', function (): void {
    $ticket = SupportTicket::factory()->open()->create();

    $result = $this->driver->updateStatus($ticket, SupportTicketStatus::InProgress);

    expect($result)->toBeTrue()
        ->and($ticket->fresh()->status)->toBe(SupportTicketStatus::InProgress);
});

it('has database driver sync methods with default behavior', function (): void {
    $ticket = SupportTicket::factory()->create();

    expect($this->driver->syncTicket($ticket))->toBeFalse()
        ->and($this->driver->getExternalTicket('123'))->toBeNull()
        ->and($this->driver->createExternalTicket($ticket))->toBeNull()
        ->and($this->driver->updateExternalTicket($ticket))->toBeNull()
        ->and($this->driver->deleteExternalTicket($ticket))->toBeTrue();
});

it('returns correct driver name', function (): void {
    expect($this->driver->getDriverName())->toBe('database');
});

it('can resolve driver from container', function (): void {
    $driver = app(SupportTicketProvider::class);

    expect($driver)->toBeInstanceOf(DatabaseDriver::class)
        ->and($driver->getDriverName())->toBe('database');
});

it('can resolve driver manager from container', function (): void {
    $manager = app('support-ticket');

    expect($manager)->toBeInstanceOf(App\Managers\SupportTicketManager::class);

    $driver = $manager->driver();

    expect($driver)->toBeInstanceOf(DatabaseDriver::class);
});
