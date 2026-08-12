<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'price_cents' => fake()->numberBetween(1000, 5000),
            'free_above_cents' => null,
            'min_order_cents' => null,
            'max_order_cents' => null,
            'estimated_days_min' => 3,
            'estimated_days_max' => 10,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function freeAbove(int $cents): static
    {
        return $this->state(fn (array $attributes) => [
            'free_above_cents' => $cents,
        ]);
    }
}
