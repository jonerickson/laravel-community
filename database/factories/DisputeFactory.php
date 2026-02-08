<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DisputeReason;
use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispute>
 */
class DisputeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'external_dispute_id' => 'dp_'.$this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'external_charge_id' => 'ch_'.$this->faker->regexify('[A-Za-z0-9]{24}'),
            'external_payment_intent_id' => 'pi_'.$this->faker->regexify('[A-Za-z0-9]{24}'),
            'status' => $this->faker->randomElement(DisputeStatus::cases()),
            'reason' => $this->faker->randomElement(DisputeReason::cases()),
            'amount' => $this->faker->numberBetween(100, 100000),
            'currency' => 'usd',
            'evidence_due_by' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'is_charge_refundable' => $this->faker->boolean(),
            'network_reason_code' => $this->faker->optional()->numerify('####'),
            'metadata' => null,
        ];
    }
}
