<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(1000, 20000);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'product_slug' => fake()->slug(),
            'variant_name' => fake()->randomElement(['P / Azul', 'M / Preto', 'Unico']),
            'variant_sku' => strtoupper(fake()->bothify('SKU-####')),
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'original_unit_price_cents' => null,
            'line_total_cents' => $unitPrice * $quantity,
        ];
    }
}
