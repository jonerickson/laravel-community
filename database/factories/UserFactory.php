<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole(Role::User);
        });
    }

    public function definition(): array
    {
        return [
            'reference_id' => $this->faker->uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'onboarded_at' => now(),
            'stripe_id' => null,
            'billing_address' => null,
            'billing_address_line_2' => null,
            'billing_city' => null,
            'billing_state' => null,
            'billing_postal_code' => null,
            'billing_country' => null,
            'vat_id' => null,
            'extra_billing_information' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function notOnboarded(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarded_at' => null,
        ]);
    }

    public function asAdmin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Role::Administrator));
    }

    public function asSupportAgent(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Role::SupportAgent));
    }
}
