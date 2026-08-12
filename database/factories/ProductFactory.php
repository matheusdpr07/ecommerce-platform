<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => ucfirst($name),
            'slug' => Product::generateUniqueSlug($name),
            'description' => fake()->optional()->paragraph(),
            'is_active' => true,
            'meta_title' => fake()->optional()->sentence(4),
            'meta_description' => fake()->optional()->sentence(8),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withVariant(array $attributes = []): static
    {
        return $this->afterCreating(function (Product $product) use ($attributes): void {
            ProductVariant::factory()->for($product)->create($attributes);
        });
    }
}
