<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'mercado_pago',
            'provider_order_id' => 'ORD'.strtoupper(Str::random(24)),
            'provider_payment_id' => 'PAY'.strtoupper(Str::random(24)),
            'method' => 'pix',
            'status' => PaymentStatus::Pending,
            'status_detail' => 'waiting_transfer',
            'amount_cents' => fake()->numberBetween(1000, 100000),
            'idempotency_key' => (string) Str::uuid(),
            'expires_at' => now()->addMinutes(30),
            'provider_payload' => [],
        ];
    }
}
