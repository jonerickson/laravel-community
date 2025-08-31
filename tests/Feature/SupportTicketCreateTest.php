<?php

declare(strict_types=1);

use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;

it('can access support ticket creation page', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('support.create'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('support/create')
        ->has('categories')
    );
});

it('can create support ticket through web form', function (): void {
    $user = User::factory()->create();
    $category = SupportTicketCategory::factory()->create();

    $ticketData = [
        'subject' => 'Test support request',
        'description' => 'This is a test support ticket description.',
        'support_ticket_category_id' => $category->id,
    ];

    $response = $this
        ->actingAs($user)
        ->post(route('support.store'), $ticketData);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Support ticket created successfully!');

    $this->assertDatabaseHas(SupportTicket::class, [
        'subject' => $ticketData['subject'],
        'description' => $ticketData['description'],
        'support_ticket_category_id' => $category->id,
        'created_by' => $user->id,
        'status' => 'new',
    ]);
});
