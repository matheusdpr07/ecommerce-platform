<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('dashboard shows net commercial and operational indicators for the selected period', function () {
    Carbon::setTestNow('2026-08-12 12:00:00');

    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();

    $approvedOrder = Order::factory()->for($customer)->create([
        'number' => 'PED-00000901',
        'status' => OrderStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Pending,
        'total_cents' => 10000,
        'placed_at' => now()->subDay(),
    ]);
    Payment::factory()->for($approvedOrder)->create([
        'status' => PaymentStatus::Approved,
        'amount_cents' => 10000,
        'paid_at' => now()->subDay(),
    ]);

    $partialOrder = Order::factory()->for($customer)->create([
        'status' => OrderStatus::PartiallyRefunded,
        'fulfillment_status' => FulfillmentStatus::Preparing,
        'total_cents' => 8000,
        'placed_at' => now()->subDays(2),
    ]);
    Payment::factory()->for($partialOrder)->create([
        'status' => PaymentStatus::PartiallyRefunded,
        'amount_cents' => 8000,
        'refunded_amount_cents' => 3000,
        'paid_at' => now()->subDays(2),
        'refunded_at' => now()->subDay(),
    ]);

    $refundedOrder = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Refunded,
        'fulfillment_status' => FulfillmentStatus::Cancelled,
        'total_cents' => 5000,
        'placed_at' => now()->subDays(3),
    ]);
    Payment::factory()->for($refundedOrder)->create([
        'status' => PaymentStatus::Refunded,
        'amount_cents' => 5000,
        'refunded_amount_cents' => 5000,
        'paid_at' => now()->subDays(3),
        'refunded_at' => now(),
    ]);

    $pendingOrder = Order::factory()->for($customer)->create([
        'status' => OrderStatus::PendingPayment,
        'placed_at' => now(),
    ]);
    Payment::factory()->for($pendingOrder)->create([
        'status' => PaymentStatus::Pending,
        'amount_cents' => $pendingOrder->total_cents,
        'paid_at' => null,
    ]);

    $product = Product::factory()->create(['is_active' => true]);
    ProductVariant::factory()->for($product)->create([
        'stock_quantity' => 3,
        'low_stock_threshold' => 5,
        'is_active' => true,
    ]);
    ProductVariant::factory()->for($product)->create([
        'stock_quantity' => 0,
        'low_stock_threshold' => 5,
        'is_active' => true,
    ]);
    AdminAuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'inventory.adjusted',
        'description' => 'Saldo de estoque atualizado.',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['period' => 7]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->where('period', 7)
            ->where('metrics.net_revenue_cents', 15000)
            ->where('metrics.paid_orders', 3)
            ->where('metrics.average_ticket_cents', 5000)
            ->where('metrics.pending_payments', 1)
            ->where('metrics.refunds_count', 2)
            ->where('metrics.refunds_amount_cents', 8000)
            ->where('metrics.new_customers', 1)
            ->where('operations.awaiting_fulfillment', 1)
            ->where('operations.preparing', 1)
            ->where('operations.low_stock', 1)
            ->where('operations.out_of_stock', 1)
            ->has('daily_trend', 7)
            ->has('recent_orders', 4)
            ->has('recent_activity', 1)
        );
});

test('dashboard falls back to thirty days for unsupported periods', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['period' => 365]))
        ->assertInertia(fn ($page) => $page
            ->where('period', 30)
            ->has('daily_trend', 30)
        );
});
