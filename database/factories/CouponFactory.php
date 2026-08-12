<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CUPOM-####')),
            'name' => fake()->words(3, true),
            'type' => DiscountType::Percentage,
            'value' => 10,
            'min_order_cents' => null,
            'max_discount_cents' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function fixedAmount(int $valueCents = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::FixedAmount,
            'value' => $valueCents,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
