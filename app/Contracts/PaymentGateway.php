<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGateway
{
    public function isConfigured(): bool;

    /**
     * @return array<string, mixed>
     */
    public function createPix(Order $order, Payment $payment): array;

    /**
     * @return array<string, mixed>
     */
    public function findOrder(string $providerOrderId): array;

    /**
     * @return array<string, mixed>
     */
    public function refundOrder(string $providerOrderId, string $idempotencyKey): array;
}
