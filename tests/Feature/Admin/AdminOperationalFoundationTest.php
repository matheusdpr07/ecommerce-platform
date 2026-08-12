<?php

use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AdminAuditService;
use Illuminate\Http\Request;

test('operational records start with safe defaults', function () {
    $order = Order::factory()->create();
    $variant = ProductVariant::factory()->create();

    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Pending)
        ->and($variant->low_stock_threshold)->toBe(5);
});

test('administrative actions can be audited with request context', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create();

    $request = Request::create('/admin/orders/'.$order->id, 'PATCH', server: [
        'REMOTE_ADDR' => '192.0.2.10',
        'HTTP_USER_AGENT' => 'Admin test browser',
    ]);

    $log = app(AdminAuditService::class)->record(
        $admin,
        'order.note_updated',
        $order,
        'Observacao interna atualizada.',
        ['fields' => ['internal_notes']],
        $request,
    );

    expect($log->user->is($admin))->toBeTrue()
        ->and($log->auditable->is($order))->toBeTrue()
        ->and($log->metadata)->toBe(['fields' => ['internal_notes']])
        ->and($log->ip_address)->toBe('192.0.2.10')
        ->and($log->user_agent)->toBe('Admin test browser');
});
