<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    public function __construct(
        private readonly AdminAuditService $auditService,
    ) {}

    public function updateStatus(
        Order $order,
        User $admin,
        FulfillmentStatus $targetStatus,
        ?string $trackingCode = null,
        ?string $trackingUrl = null,
    ): Order {
        return DB::transaction(function () use ($order, $admin, $targetStatus, $trackingCode, $trackingUrl): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();
            $currentStatus = $lockedOrder->fulfillment_status;

            if (! in_array($lockedOrder->status, [OrderStatus::Paid, OrderStatus::PartiallyRefunded], true)) {
                throw ValidationException::withMessages([
                    'fulfillment_status' => 'A operacao logistica so pode avancar apos a confirmacao do pagamento.',
                ]);
            }

            if ($targetStatus !== $currentStatus && ! $currentStatus->canTransitionTo($targetStatus)) {
                throw ValidationException::withMessages([
                    'fulfillment_status' => 'A etapa informada nao e a proxima etapa valida deste pedido.',
                ]);
            }

            if ($targetStatus === $currentStatus && $currentStatus !== FulfillmentStatus::Shipped) {
                throw ValidationException::withMessages([
                    'fulfillment_status' => 'O pedido ja esta nesta etapa.',
                ]);
            }

            $data = [
                'fulfillment_status' => $targetStatus,
            ];

            if (in_array($targetStatus, [FulfillmentStatus::Shipped, FulfillmentStatus::Delivered], true)) {
                $data['tracking_code'] = $targetStatus === FulfillmentStatus::Delivered
                    ? ($trackingCode ?? $lockedOrder->tracking_code)
                    : $trackingCode;
                $data['tracking_url'] = $targetStatus === FulfillmentStatus::Delivered
                    ? ($trackingUrl ?? $lockedOrder->tracking_url)
                    : $trackingUrl;
            }

            if ($targetStatus !== $currentStatus) {
                $timestampField = match ($targetStatus) {
                    FulfillmentStatus::Preparing => 'preparing_at',
                    FulfillmentStatus::Shipped => 'shipped_at',
                    FulfillmentStatus::Delivered => 'delivered_at',
                    default => null,
                };

                if ($timestampField !== null) {
                    $data[$timestampField] = now();
                }
            }

            $lockedOrder->update($data);

            $this->auditService->record(
                $admin,
                $targetStatus === $currentStatus ? 'order.tracking_updated' : 'order.fulfillment_updated',
                $lockedOrder,
                $targetStatus === $currentStatus
                    ? 'Dados de rastreio atualizados.'
                    : 'Etapa logistica do pedido atualizada.',
                [
                    'status_before' => $currentStatus->value,
                    'status_after' => $targetStatus->value,
                    'tracking_code' => $trackingCode,
                    'has_tracking_url' => $trackingUrl !== null,
                ],
            );

            return $lockedOrder->fresh();
        });
    }

    public function updateInternalNotes(Order $order, User $admin, ?string $notes): Order
    {
        return DB::transaction(function () use ($order, $admin, $notes): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();
            $normalizedNotes = filled($notes) ? trim($notes) : null;
            $hadNotes = filled($lockedOrder->internal_notes);

            $lockedOrder->update(['internal_notes' => $normalizedNotes]);

            if ($lockedOrder->wasChanged('internal_notes')) {
                $this->auditService->record(
                    $admin,
                    'order.notes_updated',
                    $lockedOrder,
                    'Observacao interna do pedido atualizada.',
                    [
                        'had_notes_before' => $hadNotes,
                        'has_notes_after' => $normalizedNotes !== null,
                    ],
                );
            }

            return $lockedOrder->fresh();
        });
    }
}
