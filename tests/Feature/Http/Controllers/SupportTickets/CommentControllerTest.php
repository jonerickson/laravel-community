<?php

declare(strict_types=1);

use App\Enums\SupportTicketStatus;
use App\Managers\SupportTicketManager;
use App\Models\Comment;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Comment Store Tests
|--------------------------------------------------------------------------
*/

it('can add comment to own ticket', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->post(route('support.comments.store', $ticket->reference_id), [
        'content' => 'This is a test comment on the support ticket.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');

    $this->assertDatabaseHas('comments', [
        'commentable_type' => SupportTicket::class,
        'commentable_id' => $ticket->id,
        'content' => 'This is a test comment on the support ticket.',
        'created_by' => $user->id,
    ]);
});

it('redirects guests to login when adding comment', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->post(route('support.comments.store', $ticket->reference_id), [
        'content' => 'This is a test comment.',
    ]);

    $response->assertRedirect(route('login'));
});

it('returns 403 when adding comment to other user ticket', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $otherUser->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->post(route('support.comments.store', $ticket->reference_id), [
        'content' => 'This is a test comment.',
    ]);

    $response->assertForbidden();
});

it('returns validation error when comment content is missing', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->post(route('support.comments.store', $ticket->reference_id), []);

    $response->assertSessionHasErrors('content');
});

it('returns validation error when comment content is too short', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->post(route('support.comments.store', $ticket->reference_id), [
        'content' => 'A',
    ]);

    $response->assertSessionHasErrors('content');
});

it('returns validation error when comment content is too long', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->post(route('support.comments.store', $ticket->reference_id), [
        'content' => str_repeat('a', 10001),
    ]);

    $response->assertSessionHasErrors('content');
});

it('returns 404 when adding comment to non-existent ticket', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('support.comments.store', 'non-existent-id'), [
        'content' => 'This is a test comment.',
    ]);

    $response->assertNotFound();
});

it('re-opens ticket when author adds comment to resolved ticket', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->resolved()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    expect($ticket->status)->toBe(SupportTicketStatus::Resolved);

    $response = $this->actingAs($user)->post(route('support.comments.store', $ticket->reference_id), [
        'content' => 'I have another question about this issue.',
    ]);

    $response->assertRedirect();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open);
});

it('sets ticket to waiting on customer when staff adds comment', function (): void {
    $user = User::factory()->create();
    $staffUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => $staffUser->id,
    ]);

    // Staff adds a comment (simulating via manager directly since web route checks ticket ownership)
    $manager = app(SupportTicketManager::class);
    $manager->addComment($ticket, 'Here is the information you requested.', $staffUser->id);

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::WaitingOnCustomer);
});

it('assigns ticket to commenter if unassigned', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    expect($ticket->assigned_to)->toBeNull();

    $response = $this->actingAs($user)->post(route('support.comments.store', $ticket->reference_id), [
        'content' => 'This is a follow-up comment.',
    ]);

    $response->assertRedirect();

    expect($ticket->fresh()->assigned_to)->toBe($user->id);
});

/*
|--------------------------------------------------------------------------
| Comment Destroy Tests
|--------------------------------------------------------------------------
*/

it('can delete own comment from own ticket', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $comment = Comment::create([
        'commentable_type' => SupportTicket::class,
        'commentable_id' => $ticket->id,
        'content' => 'This is a comment to delete.',
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('support.comments.destroy', [
        'ticket' => $ticket->reference_id,
        'comment' => $comment->id,
    ]));

    $response->assertRedirect();
    $response->assertSessionHas('message');

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

it('redirects guests to login when deleting comment', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $comment = Comment::create([
        'commentable_type' => SupportTicket::class,
        'commentable_id' => $ticket->id,
        'content' => 'This is a comment to delete.',
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->delete(route('support.comments.destroy', [
        'ticket' => $ticket->reference_id,
        'comment' => $comment->id,
    ]));

    $response->assertRedirect(route('login'));
});

it('returns 403 when deleting comment from other user ticket', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $otherUser->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $comment = Comment::create([
        'commentable_type' => SupportTicket::class,
        'commentable_id' => $ticket->id,
        'content' => 'This is a comment to delete.',
        'created_by' => $otherUser->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('support.comments.destroy', [
        'ticket' => $ticket->reference_id,
        'comment' => $comment->id,
    ]));

    $response->assertForbidden();
});

it('returns 403 when deleting other user comment from own ticket', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    // Comment created by other user (e.g., support staff)
    $comment = Comment::create([
        'commentable_type' => SupportTicket::class,
        'commentable_id' => $ticket->id,
        'content' => 'This is a comment from support staff.',
        'created_by' => $otherUser->id,
        'is_approved' => true,
    ]);

    // Ticket owner trying to delete staff comment - should fail because CommentPolicy checks authorship
    $response = $this->actingAs($user)->delete(route('support.comments.destroy', [
        'ticket' => $ticket->reference_id,
        'comment' => $comment->id,
    ]));

    $response->assertForbidden();
});

it('returns 404 when deleting comment from non-existent ticket', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $comment = Comment::create([
        'commentable_type' => SupportTicket::class,
        'commentable_id' => $ticket->id,
        'content' => 'This is a comment to delete.',
        'created_by' => $user->id,
        'is_approved' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('support.comments.destroy', [
        'ticket' => 'non-existent-id',
        'comment' => $comment->id,
    ]));

    $response->assertNotFound();
});

it('returns 404 when deleting non-existent comment', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->delete(route('support.comments.destroy', [
        'ticket' => $ticket->reference_id,
        'comment' => 99999,
    ]));

    $response->assertNotFound();
});
