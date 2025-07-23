<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = $this->faker->optional(0.7)->dateTimeBetween('-1 week', '+1 week');
        $endsAt = $startsAt ? $this->faker->optional(0.8)->dateTimeBetween($startsAt, '+1 month') : null;

        return [
            'title' => $this->faker->sentence(rand(3, 8)),
            'content' => $this->faker->paragraph(rand(2, 5)),
            'type' => $this->faker->randomElement(AnnouncementType::cases()),
            'is_active' => $this->faker->boolean(80),
            'is_dismissible' => $this->faker->boolean(70),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the announcement is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the announcement is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the announcement is current (no start/end dates).
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    /**
     * Indicate that the announcement is scheduled for the future.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $this->faker->dateTimeBetween('+1 day', '+1 week'),
            'ends_at' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
        ]);
    }

    /**
     * Indicate that the announcement has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'ends_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    /**
     * Create an announcement of a specific type.
     */
    public function type(AnnouncementType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Create an info announcement.
     */
    public function info(): static
    {
        return $this->type(AnnouncementType::Info);
    }

    /**
     * Create a success announcement.
     */
    public function success(): static
    {
        return $this->type(AnnouncementType::Success);
    }

    /**
     * Create a warning announcement.
     */
    public function warning(): static
    {
        return $this->type(AnnouncementType::Warning);
    }

    /**
     * Create an error announcement.
     */
    public function error(): static
    {
        return $this->type(AnnouncementType::Error);
    }
}
