<?php

namespace Database\Factories;

use App\Enums\StockMovementReason;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $quantityAfter = fake()->numberBetween(0, 100);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'user_id' => User::factory()->admin(),
            'quantity_change' => $quantityAfter,
            'quantity_after' => $quantityAfter,
            'reason' => StockMovementReason::Initial,
            'notes' => null,
        ];
    }
}
