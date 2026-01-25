<?php

declare(strict_types=1);

use App\Enums\SupportTicketStatus;
use App\Managers\SupportTicketManager;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Support Ticket Index Tests
|--------------------------------------------------------------------------
*/

it('redirects guests to knowledge base from support tickets index', function (): void {
    $response = $this->get(route('support.index'));

    $response->assertRedirect(route('knowledge-base.index'));
});

it('can view support tickets index as authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('support.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('support/index'));
});

it('displays user own tickets on index', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->get(route('support.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('support/index')
        ->has('tickets.data', 1)
        ->where('tickets.data.0.id', $ticket->id)
    );
});

it('does not display other users tickets on index', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    SupportTicket::factory()->open()->create([
        'created_by' => $otherUser->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->get(route('support.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('support/index')
        ->has('tickets.data', 0)
    );
});

it('displays multiple tickets for user on index', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    SupportTicket::factory()->count(3)->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->get(route('support.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('support/index')
        ->has('tickets.data', 3)
    );
});

/*
|--------------------------------------------------------------------------
| Support Ticket Create Tests
|--------------------------------------------------------------------------
*/

it('can view create ticket page as authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('support.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('support/create'));
});

it('redirects guests to login from create ticket page', function (): void {
    $response = $this->get(route('support.create'));

    $response->assertRedirect(route('login'));
});

it('displays categories on create page', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();

    $response = $this->actingAs($user)->get(route('support.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('support/create')
        ->has('categories', 1)
        ->where('categories.0.id', $category->id)
    );
});

it('displays user orders on create page', function (): void {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('support.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('support/create')
        ->has('orders')
    );

    $orders = Order::query()->whereBelongsTo($user)->get();
    expect($orders->contains($order))->toBeTrue();
});

it('does not display other users orders on create page', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherOrder = Order::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get(route('support.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('support/create')
        ->has('orders')
    );

    $userOrders = Order::query()->whereBelongsTo($user)->get();
    expect($userOrders->contains($otherOrder))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Support Ticket Store Tests
|--------------------------------------------------------------------------
*/

it('can create support ticket with valid data', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();

    $response = $this->actingAs($user)->post(route('support.store'), [
        'subject' => 'Test Support Ticket',
        'description' => 'This is a test support ticket description.',
        'support_ticket_category_id' => $category->id,
    ]);

    $ticket = SupportTicket::first();
    $response->assertRedirect(route('support.show', $ticket->reference_id));
    $response->assertSessionHas('message');

    expect($ticket->subject)->toBe('Test Support Ticket')
        ->and($ticket->description)->toBe('This is a test support ticket description.')
        ->and($ticket->support_ticket_category_id)->toBe($category->id)
        ->and($ticket->created_by)->toBe($user->id);
});

it('can create support ticket with order reference', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->post(route('support.store'), [
        'subject' => 'Order Issue',
        'description' => 'I have an issue with my order.',
        'support_ticket_category_id' => $category->id,
        'order_id' => $order->id,
    ]);

    $ticket = SupportTicket::first();
    $response->assertRedirect(route('support.show', $ticket->reference_id));

    expect($ticket->order_id)->toBe($order->id);
});

it('redirects guests to login when creating ticket', function (): void {
    $category = SupportTicketCategory::factory()->active()->create();

    $response = $this->post(route('support.store'), [
        'subject' => 'Test Support Ticket',
        'description' => 'This is a test description.',
        'support_ticket_category_id' => $category->id,
    ]);

    $response->assertRedirect(route('login'));
});

it('returns validation error when subject is missing', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();

    $response = $this->actingAs($user)->post(route('support.store'), [
        'description' => 'This is a test description.',
        'support_ticket_category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors('subject');
});

it('returns validation error when subject is too short', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();

    $response = $this->actingAs($user)->post(route('support.store'), [
        'subject' => 'A',
        'description' => 'This is a test description.',
        'support_ticket_category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors('subject');
});

it('returns validation error when subject is too long', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();

    $response = $this->actingAs($user)->post(route('support.store'), [
        'subject' => str_repeat('a', 256),
        'description' => 'This is a test description.',
        'support_ticket_category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors('subject');
});

it('returns validation error when description is missing', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();

    $response = $this->actingAs($user)->post(route('support.store'), [
        'subject' => 'Test Support Ticket',
        'support_ticket_category_id' => $category->id,
    ]);

    $response->assertSessionHasErrors('description');
});

it('returns validation error when category is missing', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('support.store'), [
        'subject' => 'Test Support Ticket',
        'description' => 'This is a test description.',
    ]);

    $response->assertSessionHasErrors('support_ticket_category_id');
});

it('returns validation error when category does not exist', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('support.store'), [
        'subject' => 'Test Support Ticket',
        'description' => 'This is a test description.',
        'support_ticket_category_id' => 99999,
    ]);

    $response->assertSessionHasErrors('support_ticket_category_id');
});

it('returns validation error when order belongs to another user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $order = Order::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->post(route('support.store'), [
        'subject' => 'Test Support Ticket',
        'description' => 'This is a test description.',
        'support_ticket_category_id' => $category->id,
        'order_id' => $order->id,
    ]);

    $response->assertSessionHasErrors('order_id');
});

/*
|--------------------------------------------------------------------------
| Support Ticket Show Tests
|--------------------------------------------------------------------------
*/

it('can view own ticket detail page', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->get(route('support.show', $ticket->reference_id));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('support/show')
        ->has('ticket')
        ->where('ticket.id', $ticket->id)
        ->where('ticket.subject', $ticket->subject)
    );
});

it('returns 403 when viewing other user ticket', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $otherUser->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->get(route('support.show', $ticket->reference_id));

    $response->assertForbidden();
});

it('redirects guests to login from ticket detail page', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->get(route('support.show', $ticket->reference_id));

    $response->assertRedirect(route('login'));
});

it('returns 404 for non-existent ticket', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('support.show', 'non-existent-id'));

    $response->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Support Ticket Update (Actions) Tests
|--------------------------------------------------------------------------
*/

it('can close resolved ticket', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    // Only Resolved tickets can transition to Closed
    $ticket = SupportTicket::factory()->resolved()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('support.update', $ticket->reference_id), [
        'action' => 'close',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');
    $response->assertSessionHas('messageVariant', 'success');

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Closed);
});

it('can resolve own ticket', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('support.update', $ticket->reference_id), [
        'action' => 'resolve',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');
    $response->assertSessionHas('messageVariant', 'success');

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Resolved);
});

it('can re-open resolved ticket', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    // Only Resolved tickets can transition back to Open (Closed cannot)
    $ticket = SupportTicket::factory()->resolved()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('support.update', $ticket->reference_id), [
        'action' => 'open',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('message');
    $response->assertSessionHas('messageVariant', 'success');

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open);
});

it('returns 403 when updating other user ticket', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $otherUser->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('support.update', $ticket->reference_id), [
        'action' => 'close',
    ]);

    $response->assertForbidden();
});

it('redirects guests to login when updating ticket', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->patch(route('support.update', $ticket->reference_id), [
        'action' => 'close',
    ]);

    $response->assertRedirect(route('login'));
});

it('returns validation error when action is missing', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('support.update', $ticket->reference_id), []);

    $response->assertSessionHasErrors('action');
});

it('returns validation error when action is invalid', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $response = $this->actingAs($user)->patch(route('support.update', $ticket->reference_id), [
        'action' => 'invalid_action',
    ]);

    $response->assertSessionHasErrors('action');
});

it('handles error when ticket action fails', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->active()->create();
    $ticket = SupportTicket::factory()->open()->create([
        'created_by' => $user->id,
        'support_ticket_category_id' => $category->id,
        'assigned_to' => null,
    ]);

    $this->mock(SupportTicketManager::class, function ($mock): void {
        $mock->shouldReceive('closeTicket')->andReturn(false);
    });

    $response = $this->actingAs($user)->patch(route('support.update', $ticket->reference_id), [
        'action' => 'close',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('messageVariant', 'error');
});
