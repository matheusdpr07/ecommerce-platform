<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Enums\WebhookEventStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config()->set('services.mercado_pago.access_token', 'test-access-token');
    config()->set('services.mercado_pago.webhook_secret', 'test-webhook-secret');
    config()->set('services.mercado_pago.base_url', 'https://api.mercadopago.com');
    config()->set('services.mercado_pago.webhook_tolerance_seconds', 300);
});

test('signed order webhook marks the payment and order as paid', function () {
    $order = Order::factory()->create([
        'number' => 'PED-00000100',
        'total_cents' => 7500,
    ]);
    $payment = Payment::factory()->for($order)->create([
        'provider_order_id' => 'ORD01WEBHOOKPAID',
        'provider_payment_id' => 'PAY01WEBHOOKPAID',
        'amount_cents' => 7500,
        'status' => PaymentStatus::Pending,
    ]);
    $providerPayload = mercadoPagoOrderWebhookPayload($order, 'processed', 'accredited');

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD01WEBHOOKPAID' => Http::response($providerPayload),
    ]);

    $eventPayload = mercadoPagoWebhookEvent('1001', 'ORD01WEBHOOKPAID', 'order.processed');
    $headers = mercadoPagoWebhookHeaders('ORD01WEBHOOKPAID');

    $this->postJson(
        route('webhooks.mercado-pago', ['data.id' => 'ORD01WEBHOOKPAID', 'type' => 'order']),
        $eventPayload,
        $headers,
    )->assertOk()->assertJson(['received' => true]);

    expect($payment->fresh())
        ->status->toBe(PaymentStatus::Approved)
        ->paid_at->not->toBeNull();
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    expect(WebhookEvent::query()->sole())
        ->status->toBe(WebhookEventStatus::Processed)
        ->processed_at->not->toBeNull();

    $this->postJson(
        route('webhooks.mercado-pago', ['data.id' => 'ORD01WEBHOOKPAID', 'type' => 'order']),
        $eventPayload,
        $headers,
    )->assertOk();

    expect(WebhookEvent::query()->count())->toBe(1);
    Http::assertSentCount(1);
});

test('webhook rejects invalid and expired signatures', function () {
    Http::fake();

    $this->postJson(
        route('webhooks.mercado-pago', ['data.id' => 'ORD01INVALID', 'type' => 'order']),
        mercadoPagoWebhookEvent('1002', 'ORD01INVALID', 'order.updated'),
        [
            'X-Request-Id' => (string) Str::uuid(),
            'X-Signature' => 'ts='.(now()->timestamp * 1000).',v1=invalid',
        ],
    )->assertUnauthorized();

    $this->postJson(
        route('webhooks.mercado-pago', ['data.id' => 'ORD01EXPIRED', 'type' => 'order']),
        mercadoPagoWebhookEvent('1003', 'ORD01EXPIRED', 'order.updated'),
        mercadoPagoWebhookHeaders('ORD01EXPIRED', now()->subMinutes(10)->timestamp * 1000),
    )->assertUnauthorized();

    expect(WebhookEvent::query()->count())->toBe(0);
    Http::assertNothingSent();
});

test('expired payment releases stock and coupon only once', function () {
    $product = createStorefrontProduct([], [
        'stock_quantity' => 3,
        'price_cents' => 5000,
    ]);
    $variant = $product->variants->first();
    $coupon = Coupon::factory()->create(['usage_count' => 1]);
    $order = Order::factory()->create([
        'number' => 'PED-00000200',
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
        'coupon_name' => $coupon->name,
        'subtotal_cents' => 10000,
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
        'provider_order_id' => 'ORD01WEBHOOKEXPIRED',
        'amount_cents' => 10000,
        'status' => PaymentStatus::Pending,
    ]);
    $providerPayload = mercadoPagoOrderWebhookPayload($order, 'expired', 'expired');

    Http::fake([
        'https://api.mercadopago.com/v1/orders/ORD01WEBHOOKEXPIRED' => Http::response($providerPayload),
    ]);

    foreach (['2001', '2002'] as $eventId) {
        $this->postJson(
            route('webhooks.mercado-pago', ['data.id' => 'ORD01WEBHOOKEXPIRED', 'type' => 'order']),
            mercadoPagoWebhookEvent($eventId, 'ORD01WEBHOOKEXPIRED', 'order.expired'),
            mercadoPagoWebhookHeaders('ORD01WEBHOOKEXPIRED'),
        )->assertOk();
    }

    expect($payment->fresh())
        ->status->toBe(PaymentStatus::Expired)
        ->inventory_released_at->not->toBeNull();
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect($variant->fresh()->stock_quantity)->toBe(5);
    expect($coupon->fresh()->usage_count)->toBe(0);
    expect(StockMovement::query()
        ->where('reason', StockMovementReason::OrderReversal)
        ->count())->toBe(1);
});

/**
 * @return array<string, mixed>
 */
function mercadoPagoWebhookEvent(string $eventId, string $resourceId, string $action): array
{
    return [
        'id' => $eventId,
        'type' => 'order',
        'action' => $action,
        'api_version' => 'v1',
        'data' => ['id' => $resourceId],
    ];
}

/**
 * @return array<string, string>
 */
function mercadoPagoWebhookHeaders(string $resourceId, ?int $timestamp = null): array
{
    $timestamp ??= now()->timestamp * 1000;
    $requestId = (string) Str::uuid();
    $manifest = sprintf(
        'id:%s;request-id:%s;ts:%s;',
        strtolower($resourceId),
        $requestId,
        $timestamp,
    );

    return [
        'X-Request-Id' => $requestId,
        'X-Signature' => 'ts='.$timestamp.',v1='.
            hash_hmac('sha256', $manifest, 'test-webhook-secret'),
    ];
}

/**
 * @return array<string, mixed>
 */
function mercadoPagoOrderWebhookPayload(Order $order, string $status, string $statusDetail): array
{
    return [
        'id' => $order->payment->provider_order_id,
        'type' => 'online',
        'external_reference' => $order->number,
        'total_amount' => sprintf(
            '%d.%02d',
            intdiv($order->total_cents, 100),
            $order->total_cents % 100,
        ),
        'status' => $status,
        'status_detail' => $statusDetail,
        'transactions' => [
            'payments' => [[
                'id' => $order->payment->provider_payment_id,
                'status' => $status,
                'status_detail' => $statusDetail,
                'amount' => sprintf(
                    '%d.%02d',
                    intdiv($order->total_cents, 100),
                    $order->total_cents % 100,
                ),
                'payment_method' => [
                    'id' => 'pix',
                    'type' => 'bank_transfer',
                ],
            ]],
        ],
    ];
}
