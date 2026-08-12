<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Casa', 'Trabalho', 'Entrega']),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->optional()->numerify('119########'),
            'postal_code' => fake()->numerify('########'),
            'street' => fake()->streetName(),
            'number' => (string) fake()->numberBetween(1, 9999),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => fake()->randomElement(['SP', 'RJ', 'MG', 'RS', 'PR']),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
