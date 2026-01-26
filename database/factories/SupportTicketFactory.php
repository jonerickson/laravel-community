<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement(SupportTicketStatus::cases())->value,
            'priority' => $this->faker->randomElement(SupportTicketPriority::cases())->value,
            'support_ticket_category_id' => SupportTicketCategory::factory(),
            'assigned_to' => $this->faker->boolean(60) ? User::factory() : null,
            'created_by' => User::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportTicketStatus::Open,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportTicketStatus::InProgress->value,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportTicketStatus::Resolved,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportTicketStatus::Closed,
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => SupportTicketPriority::Low,
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => SupportTicketPriority::High,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => SupportTicketPriority::Critical,
        ]);
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => User::factory(),
        ]);
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => null,
        ]);
    }

    public function external(string $driver = 'zendesk'): static
    {
        return $this->state(fn (array $attributes) => [
            'external_id' => $this->faker->uuid(),
            'external_driver' => $driver,
            'external_data' => [
                'external_url' => $this->faker->url(),
                'external_status' => $this->faker->word(),
            ],
        ]);
    }
}
