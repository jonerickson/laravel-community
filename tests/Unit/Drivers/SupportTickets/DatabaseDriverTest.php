<?php

declare(strict_types=1);

use App\Drivers\SupportTickets\DatabaseDriver;
use App\Drivers\SupportTickets\SupportTicketProvider;
use App\Enums\SupportTicketStatus;
use App\Models\Comment;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

describe('DatabaseDriver for SupportTickets', function (): void {
    beforeEach(function (): void {
        $this->driver = app(DatabaseDriver::class);
    });

    test('implements SupportTicketProvider interface', function (): void {
        expect($this->driver)->toBeInstanceOf(SupportTicketProvider::class);
    });

    test('getDriverName returns database', function (): void {
        expect($this->driver->getDriverName())->toBe('database');
    });

    describe('Ticket CRUD methods', function (): void {
        test('createTicket creates a new support ticket', function (): void {
            $user = User::factory()->create();
            $category = SupportTicketCategory::factory()->active()->create();

            $data = [
                'subject' => 'Test Ticket',
                'description' => 'Test description',
                'support_ticket_category_id' => $category->id,
                'created_by' => $user->id,
            ];

            $ticket = $this->driver->createTicket($data);

            expect($ticket)->toBeInstanceOf(SupportTicket::class);
            expect($ticket->subject)->toBe('Test Ticket');
            expect($ticket->description)->toBe('Test description');
            expect($ticket->created_by)->toBe($user->id);
            expect($ticket->support_ticket_category_id)->toBe($category->id);
        });

        test('createTicket generates ticket number automatically', function (): void {
            $user = User::factory()->create();
            $category = SupportTicketCategory::factory()->active()->create();

            $ticket = $this->driver->createTicket([
                'subject' => 'Test Ticket',
                'description' => 'Test description',
                'support_ticket_category_id' => $category->id,
                'created_by' => $user->id,
            ]);

            expect($ticket->ticket_number)->toStartWith('ST-');
        });

        test('updateTicket updates existing ticket', function (): void {
            $ticket = SupportTicket::factory()->open()->create();

            $result = $this->driver->updateTicket($ticket, [
                'subject' => 'Updated Subject',
            ]);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->subject)->toBe('Updated Subject');
        });

        test('deleteTicket deletes the ticket', function (): void {
            $ticket = SupportTicket::factory()->create();
            $ticketId = $ticket->id;

            $result = $this->driver->deleteTicket($ticket);

            expect($result)->toBeTrue();
            expect(SupportTicket::find($ticketId))->toBeNull();
        });
    });

    describe('Sync methods', function (): void {
        test('syncTicket returns false for non-external tickets', function (): void {
            $ticket = SupportTicket::factory()->create();

            $result = $this->driver->syncTicket($ticket);

            expect($result)->toBeFalse();
        });

        test('syncTicket marks external ticket as synced', function (): void {
            $ticket = SupportTicket::factory()->external('zendesk')->create();

            $result = $this->driver->syncTicket($ticket);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->last_synced_at)->not->toBeNull();
        });

        test('syncTickets syncs all external tickets needing sync', function (): void {
            SupportTicket::factory()->external('zendesk')->count(3)->create([
                'last_synced_at' => null,
            ]);

            $result = $this->driver->syncTickets();

            expect($result)->toBe(3);
        });

        test('syncTickets syncs provided collection of tickets', function (): void {
            $tickets = SupportTicket::factory()->external('zendesk')->count(2)->create([
                'last_synced_at' => null,
            ]);

            $result = $this->driver->syncTickets($tickets);

            expect($result)->toBe(2);
        });

        test('syncTickets skips non-external tickets in collection', function (): void {
            $externalTicket = SupportTicket::factory()->external('zendesk')->create([
                'last_synced_at' => null,
            ]);
            $internalTicket = SupportTicket::factory()->create();

            $result = $this->driver->syncTickets(collect([$externalTicket, $internalTicket]));

            expect($result)->toBe(1);
        });
    });

    describe('External ticket methods', function (): void {
        test('getExternalTicket returns null for database driver', function (): void {
            $result = $this->driver->getExternalTicket('ext_123');

            expect($result)->toBeNull();
        });

        test('createExternalTicket returns null for database driver', function (): void {
            $ticket = SupportTicket::factory()->create();

            $result = $this->driver->createExternalTicket($ticket);

            expect($result)->toBeNull();
        });

        test('updateExternalTicket returns null for database driver', function (): void {
            $ticket = SupportTicket::factory()->create();

            $result = $this->driver->updateExternalTicket($ticket);

            expect($result)->toBeNull();
        });

        test('deleteExternalTicket returns true for database driver', function (): void {
            $ticket = SupportTicket::factory()->create();

            $result = $this->driver->deleteExternalTicket($ticket);

            expect($result)->toBeTrue();
        });
    });

    describe('Comment methods', function (): void {
        test('addComment creates a comment on ticket', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->open()->create([
                'created_by' => $user->id,
            ]);

            $result = $this->driver->addComment($ticket, 'Test comment', $user->id);

            expect($result)->toBeTrue();
            expect($ticket->comments()->count())->toBe(1);
            expect($ticket->comments->first()->content)->toBe('Test comment');
        });

        test('addComment opens inactive ticket when comment added', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->resolved()->create([
                'created_by' => $user->id,
            ]);

            $this->driver->addComment($ticket, 'New comment', $user->id);

            expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open);
        });

        test('addComment sets status to Open when author comments on non-open ticket', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->create([
                'created_by' => $user->id,
                'status' => SupportTicketStatus::WaitingOnCustomer,
            ]);

            $this->driver->addComment($ticket, 'Author comment', $user->id);

            expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open);
        });

        test('addComment sets status to WaitingOnCustomer when staff comments', function (): void {
            $author = User::factory()->create();
            $staff = User::factory()->create();
            $ticket = SupportTicket::factory()->open()->create([
                'created_by' => $author->id,
            ]);

            $this->driver->addComment($ticket, 'Staff comment', $staff->id);

            expect($ticket->fresh()->status)->toBe(SupportTicketStatus::WaitingOnCustomer);
        });

        test('addComment assigns ticket to commenter if unassigned', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->open()->unassigned()->create();

            $this->driver->addComment($ticket, 'Comment', $user->id);

            expect($ticket->fresh()->assigned_to)->toBe($user->id);
        });

        test('addComment does not reassign already assigned ticket', function (): void {
            $assignee = User::factory()->create();
            $commenter = User::factory()->create();
            $ticket = SupportTicket::factory()->open()->create([
                'assigned_to' => $assignee->id,
            ]);

            $this->driver->addComment($ticket, 'Comment', $commenter->id);

            expect($ticket->fresh()->assigned_to)->toBe($assignee->id);
        });

        test('deleteComment deletes the comment', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->create();
            $comment = Comment::create([
                'commentable_type' => SupportTicket::class,
                'commentable_id' => $ticket->id,
                'content' => 'Test comment',
                'created_by' => $user->id,
            ]);

            $result = $this->driver->deleteComment($ticket, $comment);

            expect($result)->toBeTrue();
            expect(Comment::find($comment->id))->toBeNull();
        });
    });

    describe('Assignment methods', function (): void {
        test('assignTicket assigns ticket to user', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->unassigned()->create();

            $result = $this->driver->assignTicket($ticket, $user->id);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->assigned_to)->toBe($user->id);
        });

        test('assignTicket unassigns when null passed', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->create([
                'assigned_to' => $user->id,
            ]);

            $result = $this->driver->assignTicket($ticket, null);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->assigned_to)->toBeNull();
        });

        test('assignTicket unassigns when non-existent user id passed', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->create([
                'assigned_to' => $user->id,
            ]);

            $result = $this->driver->assignTicket($ticket, 99999);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->assigned_to)->toBeNull();
        });

        test('assignTicket accepts string user id', function (): void {
            $user = User::factory()->create();
            $ticket = SupportTicket::factory()->unassigned()->create();

            $result = $this->driver->assignTicket($ticket, (string) $user->id);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->assigned_to)->toBe($user->id);
        });
    });

    describe('Status methods', function (): void {
        test('updateStatus updates ticket status', function (): void {
            $ticket = SupportTicket::factory()->open()->create();

            $result = $this->driver->updateStatus($ticket, SupportTicketStatus::InProgress);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->status)->toBe(SupportTicketStatus::InProgress);
        });

        test('updateStatus returns false for invalid transition', function (): void {
            $ticket = SupportTicket::factory()->closed()->create();

            $result = $this->driver->updateStatus($ticket, SupportTicketStatus::Open);

            expect($result)->toBeFalse();
            expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Closed);
        });

        test('openTicket opens resolved ticket and clears timestamps', function (): void {
            $ticket = SupportTicket::factory()->resolved()->create([
                'resolved_at' => now(),
            ]);

            $result = $this->driver->openTicket($ticket);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open);
            expect($ticket->fresh()->resolved_at)->toBeNull();
            expect($ticket->fresh()->closed_at)->toBeNull();
        });

        test('openTicket returns false for closed ticket', function (): void {
            $ticket = SupportTicket::factory()->closed()->create();

            $result = $this->driver->openTicket($ticket);

            expect($result)->toBeFalse();
        });

        test('closeTicket closes resolved ticket and sets timestamp', function (): void {
            $ticket = SupportTicket::factory()->resolved()->create();

            $result = $this->driver->closeTicket($ticket);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Closed);
            expect($ticket->fresh()->closed_at)->not->toBeNull();
            expect($ticket->fresh()->resolved_at)->toBeNull();
        });

        test('closeTicket returns false for open ticket', function (): void {
            $ticket = SupportTicket::factory()->open()->create();

            $result = $this->driver->closeTicket($ticket);

            expect($result)->toBeFalse();
        });

        test('resolveTicket resolves open ticket and sets timestamp', function (): void {
            $ticket = SupportTicket::factory()->open()->create();

            $result = $this->driver->resolveTicket($ticket);

            expect($result)->toBeTrue();
            expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Resolved);
            expect($ticket->fresh()->resolved_at)->not->toBeNull();
            expect($ticket->fresh()->closed_at)->toBeNull();
        });

        test('resolveTicket returns false for closed ticket', function (): void {
            $ticket = SupportTicket::factory()->closed()->create();

            $result = $this->driver->resolveTicket($ticket);

            expect($result)->toBeFalse();
        });
    });

    describe('Attachment methods', function (): void {
        test('uploadAttachment uploads file and creates file record', function (): void {
            Storage::fake('local');

            $ticket = SupportTicket::factory()->create();
            $tempFile = tempnam(sys_get_temp_dir(), 'test');
            file_put_contents($tempFile, 'Test file content');

            $result = $this->driver->uploadAttachment($ticket, $tempFile, 'test-file.txt');

            expect($result)->not->toBeNull();
            expect($result)->toHaveKey('id');
            expect($result)->toHaveKey('name');
            expect($result)->toHaveKey('path');
            expect($result)->toHaveKey('size');
            expect($result)->toHaveKey('mime');
            expect($result['name'])->toBe('test-file.txt');
            expect($ticket->files()->count())->toBe(1);

            unlink($tempFile);
        });

        test('uploadAttachment returns null on storage failure', function (): void {
            Storage::shouldReceive('putFileAs')
                ->once()
                ->andReturn(false);

            $ticket = SupportTicket::factory()->create();
            $tempFile = tempnam(sys_get_temp_dir(), 'test');
            file_put_contents($tempFile, 'Test content');

            $result = $this->driver->uploadAttachment($ticket, $tempFile, 'test.txt');

            expect($result)->toBeNull();

            unlink($tempFile);
        });

        test('uploadAttachment returns null on exception', function (): void {
            Storage::shouldReceive('putFileAs')
                ->once()
                ->andThrow(new Exception('Storage error'));

            $ticket = SupportTicket::factory()->create();
            $tempFile = tempnam(sys_get_temp_dir(), 'test');
            file_put_contents($tempFile, 'Test content');

            $result = $this->driver->uploadAttachment($ticket, $tempFile, 'test.txt');

            expect($result)->toBeNull();

            unlink($tempFile);
        });

        test('uploadAttachment stores file in correct path', function (): void {
            Storage::fake('local');

            $ticket = SupportTicket::factory()->create();
            $tempFile = tempnam(sys_get_temp_dir(), 'test');
            file_put_contents($tempFile, 'Test content');

            $result = $this->driver->uploadAttachment($ticket, $tempFile, 'document.pdf');

            expect($result['path'])->toContain('support-tickets/'.$ticket->id);

            unlink($tempFile);
        });
    });
});
