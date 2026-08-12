<?php

use App\Enums\StockMovementReason;
use App\Models\AdminAuditLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;

test('customers cannot access inventory management', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.inventory.index'))
        ->assertForbidden();
});

test('admins can filter variants with low stock', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['is_active' => true]);
    ProductVariant::factory()->for($product)->create([
        'sku' => 'LOW-001',
        'stock_quantity' => 3,
        'low_stock_threshold' => 5,
        'is_active' => true,
    ]);
    ProductVariant::factory()->for($product)->create([
        'sku' => 'OK-001',
        'stock_quantity' => 20,
        'low_stock_threshold' => 5,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.inventory.index', ['status' => 'low_stock']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Inventory/Index')
            ->has('variants.data', 1)
            ->where('variants.data.0.sku', 'LOW-001')
            ->where('variants.data.0.stock_status', 'low_stock')
            ->where('summary.low_stock', 1)
            ->where('summary.in_stock', 1)
        );
});

test('admins can set inventory atomically with movement and audit records', function () {
    $admin = User::factory()->admin()->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->actingAs($admin)
        ->post(route('admin.inventory.adjust', $variant), [
            'operation' => 'set',
            'quantity' => 4,
            'notes' => 'Correcao apos contagem.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($variant->fresh()->stock_quantity)->toBe(4);
    expect(StockMovement::query()->sole())
        ->user_id->toBe($admin->id)
        ->quantity_change->toBe(-6)
        ->quantity_after->toBe(4)
        ->reason->toBe(StockMovementReason::ManualAdjustment)
        ->notes->toBe('Correcao apos contagem.');
    expect(AdminAuditLog::query()->sole())
        ->action->toBe('inventory.adjusted')
        ->metadata->toMatchArray([
            'quantity_before' => 10,
            'quantity_change' => -6,
            'quantity_after' => 4,
        ]);
});

test('admins can restock inventory and configure the low stock threshold', function () {
    $admin = User::factory()->admin()->create();
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 2,
        'low_stock_threshold' => 5,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.inventory.adjust', $variant), [
            'operation' => 'restock',
            'quantity' => 8,
            'notes' => 'Entrada do fornecedor.',
        ])
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->patch(route('admin.inventory.update', $variant), [
            'low_stock_threshold' => 3,
        ])
        ->assertSessionHas('success');

    expect($variant->fresh())
        ->stock_quantity->toBe(10)
        ->low_stock_threshold->toBe(3);
    expect(StockMovement::query()->sole())
        ->quantity_change->toBe(8)
        ->reason->toBe(StockMovementReason::Restock);
    expect(AdminAuditLog::query()->pluck('action')->all())->toBe([
        'inventory.adjusted',
        'inventory.threshold_updated',
    ]);
});

test('inventory rejects movements that do not change the balance', function () {
    $admin = User::factory()->admin()->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

    $this->actingAs($admin)
        ->post(route('admin.inventory.adjust', $variant), [
            'operation' => 'set',
            'quantity' => 5,
        ])
        ->assertSessionHasErrors('quantity');

    expect(StockMovement::query()->count())->toBe(0)
        ->and(AdminAuditLog::query()->count())->toBe(0);
});
