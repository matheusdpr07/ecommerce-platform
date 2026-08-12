<?php

namespace Database\Factories;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $resourceId = 'ORD'.strtoupper(Str::random(24));

        return [
            'provider' => 'mercado_pago',
            'provider_event_id' => (string) fake()->unique()->randomNumber(8),
            'event_type' => 'order',
            'action' => 'order.updated',
            'resource_id' => $resourceId,
            'request_id' => (string) Str::uuid(),
            'payload' => [
                'type' => 'order',
                'data' => ['id' => $resourceId],
            ],
            'status' => WebhookEventStatus::Pending,
        ];
    }
}
