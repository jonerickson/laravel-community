<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PolicyConsentContext;
use App\Models\Policy;
use App\Models\PolicyConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyConsent>
 */
class PolicyConsentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'policy_id' => Policy::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'fingerprint_id' => $this->faker->uuid(),
            'context' => $this->faker->randomElement(PolicyConsentContext::cases()),
            'consented_at' => $this->faker->dateTimeBetween('-30 days'),
        ];
    }
}
