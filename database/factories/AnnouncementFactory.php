<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

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

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $this->faker->dateTimeBetween('+1 day', '+1 week'),
            'ends_at' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'ends_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    public function type(AnnouncementType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    public function info(): static
    {
        return $this->type(AnnouncementType::Info);
    }

    public function success(): static
    {
        return $this->type(AnnouncementType::Success);
    }

    public function warning(): static
    {
        return $this->type(AnnouncementType::Warning);
    }

    public function error(): static
    {
        return $this->type(AnnouncementType::Error);
    }
}
