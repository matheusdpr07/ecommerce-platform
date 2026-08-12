<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.mercado_pago.access_token', 'test-access-token');
    config()->set('services.mercado_pago.base_url', 'https://api.mercadopago.com');
});

test('customers cannot access administrative orders', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.orders.index'))
        ->assertForbidden();
});

test('admins can list and view orders with payment data', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'number' => 'PED-00000300',
        'status' => OrderStatus::Paid,
    ]);
    Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Approved,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Orders/Index')
            ->has('orders.data', 1)
            ->where('orders.data.0.number', 'PED-00000300')
            ->where('orders.data.0.payment.status', PaymentStatus::Approved->value)
        );

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Orders/Show')
            ->where('order.number', 'PED-00000300')
            ->where('order.payment.status', PaymentStatus::Approved->value)
            ->where('canRefund', true)
        );
});

test('admins can fully refund approved pix payment only once', function () {
    $admin = User::factory()->admin()->create();
    $product = createStorefrontProduct([], ['stock_quantity' => 4]);
    $variant = $product->variants->first();
    $order = Order::factory()->create([
        'number' => 'PED-00000400',
        'status' => OrderStatus::Paid,
        'total_cents' => 10000,
    ]);
    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_cents' => 5000,
        'line_total_cents' => 10000,
    ]);
    $payment = Payment::factory()->for($order)->create([
        'provider_order_id' => 'ORD01REFUND',
        'provider_payment_id' => 'PAY01REFUND',
        'status' => PaymentStatus::Approved,
        'amount_cents' => 10000,
        'paid_at' => now()->subHour(),
    ]);
    $providerPayload = mercadoPagoRefundedOrder($order);

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD01REFUND/refund' => Http::response($providerPayload, 201),
        'https://api.mercadopago.com/v1/orders/ORD01REFUND' => Http::response($providerPayload),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($payment->fresh())
        ->status->toBe(PaymentStatus::Refunded)
        ->refunded_amount_cents->toBe(10000)
        ->refunded_at->not->toBeNull()
        ->refund_idempotency_key->not->toBeNull()
        ->inventory_released_at->not->toBeNull();
    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
    expect($variant->fresh()->stock_quantity)->toBe(6);
    expect(StockMovement::query()
        ->where('reason', StockMovementReason::OrderReversal)
        ->count())->toBe(1);

    Http::assertSent(function (Request $request) use ($payment): bool {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.mercadopago.com/v1/orders/ORD01REFUND/refund'
            && $request->hasHeader('X-Idempotency-Key', $payment->fresh()->refund_idempotency_key);
    });

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order->fresh()))
        ->assertForbidden();
});

test('failed refund retries reuse the persisted idempotency key', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create([
        'number' => 'PED-00000500',
        'status' => OrderStatus::Paid,
        'total_cents' => 5000,
    ]);
    $payment = Payment::factory()->for($order)->create([
        'provider_order_id' => 'ORD01RETRY',
        'status' => PaymentStatus::Approved,
        'amount_cents' => 5000,
    ]);
    $providerPayload = mercadoPagoRefundedOrder($order, 'ORD01RETRY', '50.00');

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD01RETRY/refund' => Http::sequence()
            ->push(['message' => 'temporary failure'], 500)
            ->push($providerPayload, 201),
        'https://api.mercadopago.com/v1/orders/ORD01RETRY' => Http::response($providerPayload),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order))
        ->assertSessionHas('error');

    $idempotencyKey = $payment->fresh()->refund_idempotency_key;

    expect($idempotencyKey)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.orders.refund', $order))
        ->assertSessionHas('success');

    $refundRequests = Http::recorded(fn (Request $request) => $request->url()
        === 'https://api.mercadopago.com/v1/orders/ORD01RETRY/refund');

    expect($refundRequests)->toHaveCount(2);
    expect($refundRequests
        ->map(fn (array $exchange) => $exchange[0]->header('X-Idempotency-Key')[0])
        ->unique()
        ->values()
        ->all())->toBe([$idempotencyKey]);
});

/**
 * @return array<string, mixed>
 */
function mercadoPagoRefundedOrder(
    Order $order,
    string $providerOrderId = 'ORD01REFUND',
    string $amount = '100.00',
): array {
    return [
        'id' => $providerOrderId,
        'type' => 'online',
        'external_reference' => $order->number,
        'total_amount' => $amount,
        'status' => 'processed',
        'status_detail' => 'refunded',
        'transactions' => [
            'payments' => [[
                'id' => 'PAY01REFUND',
                'status' => 'refunded',
                'status_detail' => 'refunded',
                'amount' => $amount,
                'payment_method' => [
                    'id' => 'pix',
                    'type' => 'bank_transfer',
                ],
            ]],
        ],
    ];
}
