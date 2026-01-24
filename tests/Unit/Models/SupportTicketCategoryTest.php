<?php

declare(strict_types=1);

use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;

it('can create support ticket category', function (): void {
    $category = SupportTicketCategory::factory()->create([
        'name' => 'Technical Support',
    ]);

    expect($category->slug)->not()->toBeNull();

    $this->assertDatabaseHas('support_tickets_categories', [
        'id' => $category->id,
        'name' => 'Technical Support',
    ]);
});

it('generates slug from name', function (): void {
    $category = SupportTicketCategory::factory()->create([
        'name' => 'Technical Support Issues',
    ]);

    expect($category->slug)->toBe('technical-support-issues');
});

it('has many tickets relationship', function (): void {
    $category = SupportTicketCategory::factory()->create();
    $tickets = SupportTicket::factory()->count(3)->create([
        'support_ticket_category_id' => $category->id,
    ]);

    expect($category->tickets)->toHaveCount(3)
        ->and($category->tickets->pluck('id')->sort()->values())
        ->toEqual($tickets->pluck('id')->sort()->values());
});

it('has active tickets relationship', function (): void {
    $category = SupportTicketCategory::factory()->create();

    SupportTicket::factory()->new()->create(['support_ticket_category_id' => $category->id]);
    SupportTicket::factory()->open()->create(['support_ticket_category_id' => $category->id]);
    SupportTicket::factory()->inProgress()->create(['support_ticket_category_id' => $category->id]);
    SupportTicket::factory()->resolved()->create(['support_ticket_category_id' => $category->id]);
    SupportTicket::factory()->closed()->create(['support_ticket_category_id' => $category->id]);

    expect($category->tickets)->toHaveCount(5)
        ->and($category->activeTickets)->toHaveCount(3);
});

it('has working scopes', function (): void {
    SupportTicketCategory::factory()->active()->count(3)->create();
    SupportTicketCategory::factory()->inactive()->count(2)->create();

    $activeCategories = SupportTicketCategory::active()->get();

    expect($activeCategories)->toHaveCount(3);

    SupportTicketCategory::factory()->count(3)->create([
        'order' => fn (): int => fake()->numberBetween(1, 10),
    ]);

    $ordered = SupportTicketCategory::ordered()->get();

    expect($ordered)->toHaveCount(8); // 3 active + 2 inactive + 3 ordered
});

it('has factory states', function (): void {
    $activeCategory = SupportTicketCategory::factory()->active()->create();
    $inactiveCategory = SupportTicketCategory::factory()->inactive()->create();

    expect($activeCategory->is_active)->toBeTrue()
        ->and($inactiveCategory->is_active)->toBeFalse();
});

it('casts attributes correctly', function (): void {
    $category = SupportTicketCategory::factory()->create([
        'is_active' => '1',
        'order' => '5',
    ]);

    expect($category->is_active)->toBeBool()
        ->and($category->order)->toBeInt();
});
