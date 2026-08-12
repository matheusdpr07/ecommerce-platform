<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(5000, 50000);
        $discount = 0;
        $shipping = fake()->numberBetween(0, 2500);
        $total = $subtotal - $discount + $shipping;

        return [
            'user_id' => User::factory(),
            'number' => 'PED-'.fake()->unique()->numerify('########'),
            'status' => OrderStatus::PendingPayment,
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'shipping_cents' => $shipping,
            'total_cents' => $total,
            'coupon_id' => null,
            'coupon_code' => null,
            'coupon_name' => null,
            'shipping_method_id' => null,
            'shipping_method_name' => fake()->randomElement(['PAC', 'SEDEX', 'Retirada']),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->optional()->numerify('119########'),
            'postal_code' => fake()->numerify('########'),
            'street' => fake()->streetName(),
            'street_number' => (string) fake()->buildingNumber(),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['SP', 'RJ', 'MG', 'RS']),
            'placed_at' => now(),
        ];
    }
}
