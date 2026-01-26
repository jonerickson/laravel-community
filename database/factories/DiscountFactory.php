<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    public function definition(): array
    {
        $type = $this->faker->randomElement(DiscountType::cases());
        $discountType = $this->faker->randomElement(DiscountValueType::cases());

        $value = $discountType === DiscountValueType::Percentage
            ? $this->faker->numberBetween(5, 75)
            : $this->faker->numberBetween(500, 10000);

        $code = $this->generateCode($type);

        return [
            'code' => $code,
            'type' => $type,
            'discount_type' => $discountType,
            'value' => $value,
            'current_balance' => $type === DiscountType::GiftCard ? $value : null,
            'product_id' => $type === DiscountType::GiftCard ? Product::factory() : null,
            'user_id' => $type === DiscountType::GiftCard ? User::factory() : null,
            'created_by' => $type === DiscountType::Manual ? User::factory() : null,
            'recipient_email' => $type === DiscountType::GiftCard ? $this->faker->optional(0.3)->safeEmail() : null,
            'max_uses' => $type === DiscountType::PromoCode ? $this->faker->optional(0.5)->numberBetween(10, 1000) : null,
            'times_used' => 0,
            'min_order_amount' => $this->faker->optional(0.3)->numberBetween(1000, 5000),
            'expires_at' => $this->faker->optional(0.4)->dateTimeBetween('+1 week', '+1 year'),
            'activated_at' => null,
        ];
    }

    public function giftCard(int $value = 5000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::GiftCard,
            'discount_type' => DiscountValueType::Fixed,
            'value' => $value,
            'current_balance' => $value,
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'max_uses' => null,
        ]);
    }

    public function promoCode(int $percentageOff = 25): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::PromoCode,
            'discount_type' => DiscountValueType::Percentage,
            'value' => $percentageOff,
            'current_balance' => null,
            'product_id' => null,
            'user_id' => null,
            'created_by' => null,
            'max_uses' => 100,
        ]);
    }

    public function manual(int $amountOff = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::Manual,
            'discount_type' => DiscountValueType::Fixed,
            'value' => $amountOff,
            'current_balance' => null,
            'product_id' => null,
            'user_id' => null,
            'created_by' => User::factory(),
            'max_uses' => 1,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('+1 week', '+1 year'),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'activated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'times_used' => $this->faker->numberBetween(1, 5),
        ]);
    }

    public function depleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => 0,
            'activated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'times_used' => $this->faker->numberBetween(1, 10),
        ]);
    }

    protected function generateCode(DiscountType $type): string
    {
        $prefix = match ($type) {
            DiscountType::GiftCard => 'GIFT',
            DiscountType::PromoCode => 'PROMO',
            DiscountType::Manual => 'MANUAL',
            DiscountType::Cancellation => 'CANCELLATION-OFFER',
        };

        return Str::upper("{$prefix}-".Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
    }
}
