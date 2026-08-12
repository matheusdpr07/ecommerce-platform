<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Http\Requests\Admin\UpdateInventorySettingsRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        if (! in_array($status, ['', 'in_stock', 'low_stock', 'out_of_stock', 'inactive'], true)) {
            $status = '';
        }

        $variants = ProductVariant::query()
            ->with(['product:id,name,slug,is_active', 'latestStockMovement.user:id,name'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $productQuery) => $productQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            });

        $this->applyStatusFilter($variants, $status);

        $variants = $variants
            ->orderByRaw('stock_quantity <= low_stock_threshold desc')
            ->orderBy('stock_quantity')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ProductVariant $variant): array => $this->transformVariant($variant));

        return Inertia::render('Admin/Inventory/Index', [
            'variants' => $variants,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'summary' => $this->summary(),
        ]);
    }

    public function show(ProductVariant $variant): Response
    {
        $variant->load('product:id,name,slug,is_active');
        $this->authorize('update', $variant->product);

        $movements = $variant->stockMovements()
            ->with('user:id,name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($movement): array => [
                'id' => $movement->id,
                'quantity_change' => $movement->quantity_change,
                'quantity_after' => $movement->quantity_after,
                'reason' => $movement->reason->value,
                'reason_label' => $movement->reason->label(),
                'notes' => $movement->notes,
                'user' => $movement->user === null ? null : [
                    'id' => $movement->user->id,
                    'name' => $movement->user->name,
                ],
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Inventory/Show', [
            'variant' => $this->transformVariant($variant),
            'movements' => $movements,
        ]);
    }

    public function adjust(
        AdjustInventoryRequest $request,
        ProductVariant $variant,
        InventoryService $inventoryService,
    ): RedirectResponse {
        $variant->load('product');
        $this->authorize('update', $variant->product);

        $inventoryService->adjust(
            $variant,
            $request->user(),
            $request->string('operation')->toString(),
            $request->integer('quantity'),
            $request->string('notes')->toString() ?: null,
        );

        return back()->with('success', 'Estoque atualizado e movimentacao registrada.');
    }

    public function update(
        UpdateInventorySettingsRequest $request,
        ProductVariant $variant,
        InventoryService $inventoryService,
    ): RedirectResponse {
        $variant->load('product');
        $this->authorize('update', $variant->product);

        $inventoryService->updateLowStockThreshold(
            $variant,
            $request->user(),
            $request->integer('low_stock_threshold'),
        );

        return back()->with('success', 'Limite de estoque baixo atualizado.');
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    private function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'in_stock' => $query
                ->where('is_active', true)
                ->whereHas('product', fn (Builder $productQuery) => $productQuery->where('is_active', true))
                ->whereColumn('stock_quantity', '>', 'low_stock_threshold'),
            'low_stock' => $query
                ->where('is_active', true)
                ->whereHas('product', fn (Builder $productQuery) => $productQuery->where('is_active', true))
                ->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold'),
            'out_of_stock' => $query
                ->where('is_active', true)
                ->whereHas('product', fn (Builder $productQuery) => $productQuery->where('is_active', true))
                ->where('stock_quantity', 0),
            'inactive' => $query->where(function (Builder $query): void {
                $query->where('is_active', false)
                    ->orWhereHas('product', fn (Builder $productQuery) => $productQuery->where('is_active', false));
            }),
            default => null,
        };
    }

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        $available = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn (Builder $query) => $query->where('is_active', true));

        return [
            'total' => ProductVariant::query()->count(),
            'in_stock' => (clone $available)
                ->whereColumn('stock_quantity', '>', 'low_stock_threshold')
                ->count(),
            'low_stock' => (clone $available)
                ->where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count(),
            'out_of_stock' => (clone $available)->where('stock_quantity', 0)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformVariant(ProductVariant $variant): array
    {
        $isAvailable = $variant->is_active && $variant->product->is_active;
        $status = match (true) {
            ! $isAvailable => 'inactive',
            $variant->stock_quantity === 0 => 'out_of_stock',
            $variant->stock_quantity <= $variant->low_stock_threshold => 'low_stock',
            default => 'in_stock',
        };

        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'name' => $variant->name,
            'stock_quantity' => $variant->stock_quantity,
            'low_stock_threshold' => $variant->low_stock_threshold,
            'is_active' => $variant->is_active,
            'stock_status' => $status,
            'product' => [
                'id' => $variant->product->id,
                'name' => $variant->product->name,
                'slug' => $variant->product->slug,
                'is_active' => $variant->product->is_active,
            ],
            'latest_movement' => $variant->relationLoaded('latestStockMovement') && $variant->latestStockMovement !== null
                ? [
                    'quantity_change' => $variant->latestStockMovement->quantity_change,
                    'reason_label' => $variant->latestStockMovement->reason->label(),
                    'user_name' => $variant->latestStockMovement->user?->name,
                    'created_at' => $variant->latestStockMovement->created_at?->toIso8601String(),
                ]
                : null,
        ];
    }
}
