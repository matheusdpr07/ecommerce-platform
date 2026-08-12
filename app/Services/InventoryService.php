<?php

namespace App\Services;

use App\Enums\StockMovementReason;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly AdminAuditService $auditService,
    ) {}

    public function adjust(
        ProductVariant $variant,
        User $admin,
        string $operation,
        int $quantity,
        ?string $notes = null,
    ): ProductVariant {
        if (! in_array($operation, ['set', 'restock'], true)) {
            throw ValidationException::withMessages([
                'operation' => 'O tipo de movimentacao e invalido.',
            ]);
        }

        return DB::transaction(function () use ($variant, $admin, $operation, $quantity, $notes): ProductVariant {
            $lockedVariant = ProductVariant::query()
                ->whereKey($variant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStock = $lockedVariant->stock_quantity;
            $newStock = $operation === 'restock'
                ? $previousStock + $quantity
                : $quantity;

            if ($operation === 'restock' && $quantity === 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'A reposicao deve adicionar ao menos uma unidade.',
                ]);
            }

            if ($newStock === $previousStock) {
                throw ValidationException::withMessages([
                    'quantity' => 'A movimentacao precisa alterar o saldo atual.',
                ]);
            }

            if ($newStock > 4_294_967_295) {
                throw ValidationException::withMessages([
                    'quantity' => 'O saldo final ultrapassa o limite permitido.',
                ]);
            }

            $quantityChange = $newStock - $previousStock;
            $reason = $operation === 'restock'
                ? StockMovementReason::Restock
                : StockMovementReason::ManualAdjustment;

            $lockedVariant->update(['stock_quantity' => $newStock]);
            $lockedVariant->stockMovements()->create([
                'user_id' => $admin->id,
                'quantity_change' => $quantityChange,
                'quantity_after' => $newStock,
                'reason' => $reason,
                'notes' => $notes,
            ]);

            $this->auditService->record(
                $admin,
                'inventory.adjusted',
                $lockedVariant,
                'Saldo de estoque atualizado.',
                [
                    'operation' => $operation,
                    'quantity_before' => $previousStock,
                    'quantity_change' => $quantityChange,
                    'quantity_after' => $newStock,
                    'notes' => $notes,
                ],
            );

            return $lockedVariant->fresh();
        });
    }

    public function updateLowStockThreshold(
        ProductVariant $variant,
        User $admin,
        int $threshold,
    ): ProductVariant {
        return DB::transaction(function () use ($variant, $admin, $threshold): ProductVariant {
            $lockedVariant = ProductVariant::query()
                ->whereKey($variant->id)
                ->lockForUpdate()
                ->firstOrFail();
            $previousThreshold = $lockedVariant->low_stock_threshold;

            $lockedVariant->update(['low_stock_threshold' => $threshold]);

            if ($previousThreshold !== $threshold) {
                $this->auditService->record(
                    $admin,
                    'inventory.threshold_updated',
                    $lockedVariant,
                    'Limite de estoque baixo atualizado.',
                    [
                        'threshold_before' => $previousThreshold,
                        'threshold_after' => $threshold,
                    ],
                );
            }

            return $lockedVariant->fresh();
        });
    }
}
