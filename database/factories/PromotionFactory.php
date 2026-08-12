<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\PromotionScope;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => DiscountType::Percentage,
            'value' => 15,
            'scope' => PromotionScope::AllProducts,
            'category_id' => null,
            'brand_id' => null,
            'product_id' => null,
            'priority' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function forCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => PromotionScope::Category,
            'category_id' => $categoryId,
        ]);
    }

    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => PromotionScope::Product,
            'product_id' => $productId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
