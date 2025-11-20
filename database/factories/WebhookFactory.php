<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HttpMethod;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
class WebhookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'url' => $this->faker->url(),
            'event' => $this->faker->word(),
            'method' => $this->faker->randomElement(HttpMethod::cases()),
            'headers' => [],
            'payload' => [],
        ];
    }
}
