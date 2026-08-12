<?php

use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Models\User;

test('customers cannot access the administrative activity history', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.activity.index'))
        ->assertForbidden();
});

test('admins can filter activity without exposing technical context', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Operacional']);
    $order = Order::factory()->create();
    AdminAuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'order.notes_updated',
        'auditable_type' => $order->getMorphClass(),
        'auditable_id' => $order->id,
        'description' => 'Observacao interna do pedido atualizada.',
        'metadata' => ['private_context' => 'nao expor'],
        'ip_address' => '192.0.2.20',
        'user_agent' => 'Audit test browser',
        'created_at' => '2026-08-12 10:00:00',
        'updated_at' => '2026-08-12 10:00:00',
    ]);
    AdminAuditLog::query()->create([
        'user_id' => $admin->id,
        'action' => 'inventory.adjusted',
        'description' => 'Saldo de estoque atualizado.',
        'created_at' => '2026-08-01 10:00:00',
        'updated_at' => '2026-08-01 10:00:00',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.activity.index', [
            'search' => 'observacao',
            'action' => 'order.notes_updated',
            'date_from' => '2026-08-10',
            'date_to' => '2026-08-12',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Activity/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.action', 'order.notes_updated')
            ->where('logs.data.0.subject_type', 'Order')
            ->where('logs.data.0.subject_id', $order->id)
            ->where('logs.data.0.user.name', 'Admin Operacional')
            ->missing('logs.data.0.metadata')
            ->missing('logs.data.0.ip_address')
            ->missing('logs.data.0.user_agent')
            ->where('filters.date_from', '2026-08-10')
            ->where('filters.date_to', '2026-08-12')
        );
});
