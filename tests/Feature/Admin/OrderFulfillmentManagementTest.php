<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Models\User;

test('paid orders advance through fulfillment stages in sequence', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.fulfillment.update', $order), [
            'fulfillment_status' => FulfillmentStatus::Preparing->value,
        ])
        ->assertSessionHas('success');

    expect($order->fresh())
        ->fulfillment_status->toBe(FulfillmentStatus::Preparing)
        ->preparing_at->not->toBeNull();

    $this->actingAs($admin)
        ->patch(route('admin.orders.fulfillment.update', $order), [
            'fulfillment_status' => FulfillmentStatus::Shipped->value,
            'tracking_code' => 'BR123456789',
            'tracking_url' => 'https://tracking.example/BR123456789',
        ])
        ->assertSessionHas('success');

    expect($order->fresh())
        ->fulfillment_status->toBe(FulfillmentStatus::Shipped)
        ->tracking_code->toBe('BR123456789')
        ->shipped_at->not->toBeNull();

    $this->actingAs($admin)
        ->patch(route('admin.orders.fulfillment.update', $order), [
            'fulfillment_status' => FulfillmentStatus::Delivered->value,
        ])
        ->assertSessionHas('success');

    expect($order->fresh())
        ->fulfillment_status->toBe(FulfillmentStatus::Delivered)
        ->tracking_code->toBe('BR123456789')
        ->tracking_url->toBe('https://tracking.example/BR123456789')
        ->delivered_at->not->toBeNull();
    expect(AdminAuditLog::query()
        ->where('action', 'order.fulfillment_updated')
        ->count())->toBe(3);
});

test('fulfillment cannot skip stages or advance before payment', function () {
    $admin = User::factory()->admin()->create();
    $paidOrder = Order::factory()->create(['status' => OrderStatus::Paid]);
    $pendingOrder = Order::factory()->create(['status' => OrderStatus::PendingPayment]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.fulfillment.update', $paidOrder), [
            'fulfillment_status' => FulfillmentStatus::Shipped->value,
        ])
        ->assertSessionHasErrors('fulfillment_status');

    $this->actingAs($admin)
        ->patch(route('admin.orders.fulfillment.update', $pendingOrder), [
            'fulfillment_status' => FulfillmentStatus::Preparing->value,
        ])
        ->assertSessionHasErrors('fulfillment_status');

    expect($paidOrder->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Pending)
        ->and($pendingOrder->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Pending)
        ->and(AdminAuditLog::query()->count())->toBe(0);
});

test('admins can update tracking after shipment', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Shipped,
        'tracking_code' => 'OLD-CODE',
        'shipped_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.fulfillment.update', $order), [
            'fulfillment_status' => FulfillmentStatus::Shipped->value,
            'tracking_code' => 'NEW-CODE',
            'tracking_url' => 'https://tracking.example/NEW-CODE',
        ])
        ->assertSessionHas('success');

    expect($order->fresh())
        ->tracking_code->toBe('NEW-CODE')
        ->tracking_url->toBe('https://tracking.example/NEW-CODE');
    expect(AdminAuditLog::query()->sole()->action)->toBe('order.tracking_updated');
});

test('internal order notes are audited and never exposed to customers', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatus::Paid]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.notes.update', $order), [
            'internal_notes' => 'Conferir embalagem reforcada.',
        ])
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertInertia(fn ($page) => $page
            ->where('order.internal_notes', 'Conferir embalagem reforcada.')
        );

    $this->actingAs($customer)
        ->get(route('store.orders.show', $order))
        ->assertInertia(fn ($page) => $page
            ->missing('order.internal_notes')
            ->where('order.fulfillment_status', FulfillmentStatus::Pending->value)
        );

    expect(AdminAuditLog::query()->sole()->action)->toBe('order.notes_updated');
});

test('admin order listing filters by fulfillment stage and period', function () {
    $admin = User::factory()->admin()->create();
    Order::factory()->create([
        'number' => 'PED-00000801',
        'status' => OrderStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Preparing,
        'placed_at' => '2026-08-10 12:00:00',
    ]);
    Order::factory()->create([
        'number' => 'PED-00000802',
        'status' => OrderStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Shipped,
        'placed_at' => '2026-08-01 12:00:00',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index', [
            'fulfillment_status' => FulfillmentStatus::Preparing->value,
            'date_from' => '2026-08-09',
            'date_to' => '2026-08-11',
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('orders.data', 1)
            ->where('orders.data.0.number', 'PED-00000801')
            ->where('filters.fulfillment_status', FulfillmentStatus::Preparing->value)
            ->where('filters.date_from', '2026-08-09')
            ->where('filters.date_to', '2026-08-11')
        );
});
