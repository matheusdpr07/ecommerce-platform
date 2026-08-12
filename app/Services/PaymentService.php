<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\PaymentGatewayException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use DateInterval;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    public function isConfigured(): bool
    {
        return $this->gateway->isConfigured();
    }

    public function createPixForOrder(Order $order): Payment
    {
        if (! $order->status->canReceivePayment()) {
            throw ValidationException::withMessages([
                'payment' => 'Este pedido nao pode receber um novo pagamento.',
            ]);
        }

        $payment = DB::transaction(function () use ($order): Payment {
            return Payment::query()->firstOrCreate(
                ['order_id' => $order->id],
                [
                    'provider' => 'mercado_pago',
                    'method' => 'pix',
                    'status' => PaymentStatus::Pending,
                    'amount_cents' => $order->total_cents,
                    'idempotency_key' => (string) Str::uuid(),
                    'expires_at' => $this->pixExpiresAt(),
                ],
            );
        });

        if ($payment->provider_order_id !== null) {
            return $payment->fresh();
        }

        $order->loadMissing('user');
        $payload = $this->gateway->createPix($order, $payment);

        return $this->syncFromProvider($payment, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function syncFromProvider(Payment $payment, array $payload): Payment
    {
        return DB::transaction(function () use ($payment, $payload): Payment {
            $lockedPayment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->with('order.items')
                ->firstOrFail();

            $this->assertPayloadMatchesPayment($lockedPayment, $payload);

            $providerPayment = data_get($payload, 'transactions.payments.0', []);
            $paymentMethod = is_array($providerPayment)
                ? data_get($providerPayment, 'payment_method', [])
                : [];
            $providerStatus = (string) ($payload['status'] ?? data_get($providerPayment, 'status', 'created'));
            $statusDetail = (string) ($payload['status_detail'] ?? data_get($providerPayment, 'status_detail', ''));
            $status = $this->mapProviderStatus($providerStatus, $statusDetail);

            $lockedPayment->update([
                'provider_order_id' => (string) $payload['id'],
                'provider_payment_id' => data_get($providerPayment, 'id')
                    ? (string) data_get($providerPayment, 'id')
                    : $lockedPayment->provider_payment_id,
                'status' => $status,
                'status_detail' => $statusDetail !== '' ? $statusDetail : null,
                'pix_qr_code' => data_get($paymentMethod, 'qr_code', $lockedPayment->pix_qr_code),
                'pix_qr_code_base64' => data_get($paymentMethod, 'qr_code_base64', $lockedPayment->pix_qr_code_base64),
                'pix_ticket_url' => data_get($paymentMethod, 'ticket_url', $lockedPayment->pix_ticket_url),
                'paid_at' => $status === PaymentStatus::Approved
                    ? ($lockedPayment->paid_at ?? now())
                    : $lockedPayment->paid_at,
                'refunded_at' => $status === PaymentStatus::Refunded
                    ? ($lockedPayment->refunded_at ?? now())
                    : $lockedPayment->refunded_at,
                'provider_payload' => $payload,
            ]);

            $this->syncOrderStatus($lockedPayment->fresh(['order.items']));

            return $lockedPayment->fresh();
        });
    }

    public function refund(Payment $payment): Payment
    {
        if ($payment->provider_order_id === null || ! in_array($payment->status, [
            PaymentStatus::Approved,
            PaymentStatus::PartiallyRefunded,
        ], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Somente pagamentos aprovados podem ser reembolsados.',
            ]);
        }

        if ($payment->refund_idempotency_key === null) {
            $payment->update([
                'refund_idempotency_key' => (string) Str::uuid(),
            ]);
            $payment->refresh();
        }

        $payload = $this->gateway->refundOrder(
            $payment->provider_order_id,
            $payment->refund_idempotency_key,
        );

        return $this->syncFromProvider($payment, $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function transformForStore(?Payment $payment): ?array
    {
        if ($payment === null) {
            return null;
        }

        return [
            'id' => $payment->id,
            'method' => $payment->method,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'status_detail' => $payment->status_detail,
            'amount_cents' => $payment->amount_cents,
            'pix_qr_code' => $payment->pix_qr_code,
            'pix_qr_code_base64' => $payment->pix_qr_code_base64,
            'pix_ticket_url' => $payment->pix_ticket_url,
            'expires_at' => $payment->expires_at?->toIso8601String(),
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'refunded_at' => $payment->refunded_at?->toIso8601String(),
            'can_retry' => $payment->provider_order_id === null
                && $payment->order->status === OrderStatus::PendingPayment,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertPayloadMatchesPayment(Payment $payment, array $payload): void
    {
        $providerOrderId = $payload['id'] ?? null;

        if (! is_string($providerOrderId) || $providerOrderId === '') {
            throw new PaymentGatewayException('A resposta do pagamento nao possui identificador.');
        }

        if ($payment->provider_order_id !== null && $payment->provider_order_id !== $providerOrderId) {
            throw new PaymentGatewayException('O identificador do pagamento nao corresponde ao pedido.');
        }

        if (($payload['external_reference'] ?? null) !== $payment->order->number) {
            throw new PaymentGatewayException('A referencia externa do pagamento nao corresponde ao pedido.');
        }

        if (isset($payload['total_amount'])
            && $this->decimalToCents((string) $payload['total_amount']) !== $payment->amount_cents
        ) {
            throw new PaymentGatewayException('O valor do pagamento nao corresponde ao pedido.');
        }
    }

    private function mapProviderStatus(string $status, string $statusDetail): PaymentStatus
    {
        if ($status === 'processed' && $statusDetail === 'partially_refunded') {
            return PaymentStatus::PartiallyRefunded;
        }

        return match ($status) {
            'created', 'action_required' => PaymentStatus::Pending,
            'processing', 'in_review' => PaymentStatus::Processing,
            'processed' => PaymentStatus::Approved,
            'failed' => PaymentStatus::Failed,
            'canceled' => PaymentStatus::Cancelled,
            'expired' => PaymentStatus::Expired,
            'refunded' => PaymentStatus::Refunded,
            'charged_back' => PaymentStatus::ChargedBack,
            default => PaymentStatus::Processing,
        };
    }

    private function syncOrderStatus(Payment $payment): void
    {
        $order = $payment->order;

        match ($payment->status) {
            PaymentStatus::Approved => $this->markOrderPaid($payment, $order),
            PaymentStatus::Failed => $this->closeOrderAndReleaseInventory($payment, $order, OrderStatus::PaymentFailed),
            PaymentStatus::Cancelled, PaymentStatus::Expired => $this->closeOrderAndReleaseInventory($payment, $order, OrderStatus::Cancelled),
            PaymentStatus::PartiallyRefunded => $this->markOrderPartiallyRefunded($order),
            PaymentStatus::Refunded => $this->closeOrderAndReleaseInventory($payment, $order, OrderStatus::Refunded),
            PaymentStatus::ChargedBack => $this->markOrderChargedBack($order),
            default => null,
        };
    }

    private function markOrderPaid(Payment $payment, Order $order): void
    {
        if ($payment->inventory_released_at !== null) {
            throw new PaymentGatewayException('O estoque deste pedido ja foi liberado.');
        }

        if ($order->status === OrderStatus::PendingPayment) {
            $order->update(['status' => OrderStatus::Paid]);
        }
    }

    private function markOrderPartiallyRefunded(Order $order): void
    {
        if ($order->status === OrderStatus::Paid) {
            $order->update(['status' => OrderStatus::PartiallyRefunded]);
        }
    }

    private function markOrderChargedBack(Order $order): void
    {
        if (in_array($order->status, [OrderStatus::Paid, OrderStatus::PartiallyRefunded], true)) {
            $order->update(['status' => OrderStatus::ChargedBack]);
        }
    }

    private function closeOrderAndReleaseInventory(
        Payment $payment,
        Order $order,
        OrderStatus $status,
    ): void {
        $allowedStatuses = $status === OrderStatus::Refunded
            ? [OrderStatus::Paid, OrderStatus::PartiallyRefunded]
            : [OrderStatus::PendingPayment];

        if (! in_array($order->status, $allowedStatuses, true)) {
            return;
        }

        $this->releaseInventoryAndCoupon($payment, $order);
        $order->update(['status' => $status]);
    }

    private function releaseInventoryAndCoupon(Payment $payment, Order $order): void
    {
        if ($payment->inventory_released_at !== null) {
            return;
        }

        $variants = ProductVariant::query()
            ->whereIn('id', $order->items->pluck('product_variant_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($order->items as $item) {
            $variant = $variants->get($item->product_variant_id);

            if ($variant === null) {
                throw new PaymentGatewayException('Nao foi possivel restaurar o estoque do pedido.');
            }

            $newStock = $variant->stock_quantity + $item->quantity;
            $variant->update(['stock_quantity' => $newStock]);
            $variant->stockMovements()->create([
                'user_id' => null,
                'quantity_change' => $item->quantity,
                'quantity_after' => $newStock,
                'reason' => StockMovementReason::OrderReversal,
                'notes' => "Reversao do pedido {$order->number}",
            ]);
        }

        if ($order->coupon_id !== null) {
            $coupon = Coupon::query()->whereKey($order->coupon_id)->lockForUpdate()->first();

            if ($coupon !== null && $coupon->usage_count > 0) {
                $coupon->decrement('usage_count');
            }
        }

        $payment->update(['inventory_released_at' => now()]);
    }

    private function decimalToCents(string $amount): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new PaymentGatewayException('O Mercado Pago retornou um valor invalido.');
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return ((int) $matches[1] * 100) + (int) $fraction;
    }

    private function pixExpiresAt(): Carbon
    {
        try {
            $interval = new DateInterval((string) config('services.mercado_pago.pix_expiration'));

            return now()->add($interval);
        } catch (Throwable $exception) {
            throw new PaymentGatewayException(
                'O prazo de expiracao do Pix esta configurado incorretamente.',
                previous: $exception,
            );
        }
    }
}
