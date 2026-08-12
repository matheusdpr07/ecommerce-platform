<?php

use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.mercado_pago.access_token', 'test-access-token');
    config()->set('services.mercado_pago.base_url', 'https://api.mercadopago.com');
    config()->set('services.mercado_pago.sandbox', false);
    config()->set('services.mercado_pago.sandbox_payer_email');
    config()->set('services.mercado_pago.pix_expiration', 'PT30M');
});

test('customers can generate an idempotent pix for their order', function () {
    $user = User::factory()->create(['email' => 'cliente@example.com']);
    $order = Order::factory()->for($user)->create([
        'number' => 'PED-00000010',
        'total_cents' => 12345,
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response(
            mercadoPagoPixPayload($order),
            201,
        ),
    ]);

    $this->actingAs($user)
        ->post(route('store.orders.payment.pix', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    $payment = Payment::query()->sole();

    expect($payment)
        ->order_id->toBe($order->id)
        ->provider_order_id->toBe('ORD01TESTPAYMENT')
        ->provider_payment_id->toBe('PAY01TESTPAYMENT')
        ->status->toBe(PaymentStatus::Pending)
        ->amount_cents->toBe(12345)
        ->pix_qr_code->toBe('pix-copia-e-cola')
        ->pix_qr_code_base64->toBe('base64-qr-code')
        ->pix_ticket_url->toBe('https://www.mercadopago.com.br/payments/test/ticket');

    Http::assertSent(function (Request $request) use ($payment): bool {
        return $request->url() === 'https://api.mercadopago.com/v1/orders'
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && $request->hasHeader('X-Idempotency-Key', $payment->idempotency_key)
            && $request['type'] === 'online'
            && $request['total_amount'] === '123.45'
            && $request['external_reference'] === 'PED-00000010'
            && data_get($request->data(), 'transactions.payments.0.payment_method.id') === 'pix'
            && data_get($request->data(), 'payer.email') === 'cliente@example.com';
    });

    $this->actingAs($user)
        ->post(route('store.orders.payment.pix', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Payment::query()->count())->toBe(1);
    Http::assertSentCount(1);
});

test('checkout creates the order and its pix when the gateway is configured', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 10000,
        'stock_quantity' => 2,
    ]);
    $address = Address::factory()->for($user)->create();
    $shippingMethod = ShippingMethod::factory()->create(['price_cents' => 900]);
    $cart = Cart::factory()->for($user)->create([
        'shipping_address_id' => $address->id,
        'shipping_method_id' => $shippingMethod->id,
        'shipping_cents' => 900,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    Http::fake(function (Request $request) {
        return Http::response(mercadoPagoProviderPayload(
            (string) $request['external_reference'],
            (string) $request['total_amount'],
        ), 201);
    });

    $this->actingAs($user)
        ->post(route('store.checkout.store'))
        ->assertRedirect()
        ->assertSessionHas('success');

    $order = Order::query()->sole();

    expect($order->payment)
        ->not->toBeNull()
        ->amount_cents->toBe(10900)
        ->provider_order_id->toBe('ORD01TESTPAYMENT');
});

test('sandbox pix uses the configured mercado pago test buyer', function () {
    config()->set('services.mercado_pago.sandbox', true);
    config()->set('services.mercado_pago.sandbox_payer_email', 'comprador@testuser.com');

    $user = User::factory()->create(['email' => 'cliente-local@example.com']);
    $order = Order::factory()->for($user)->create([
        'number' => 'PED-00000011',
        'total_cents' => 5000,
    ]);

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response(
            mercadoPagoPixPayload($order),
            201,
        ),
    ]);

    $this->actingAs($user)
        ->post(route('store.orders.payment.pix', $order))
        ->assertSessionHas('success');

    Http::assertSent(fn (Request $request): bool => data_get(
        $request->data(),
        'payer.email',
    ) === 'comprador@testuser.com');
});

test('sandbox pix requires a mercado pago test buyer', function () {
    config()->set('services.mercado_pago.sandbox', true);

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    Http::fake();

    $this->actingAs($user)
        ->post(route('store.orders.payment.pix', $order))
        ->assertSessionHas('error');

    Http::assertNothingSent();
});

test('customers cannot generate pix for another users order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for(User::factory())->create();

    Http::fake();

    $this->actingAs($user)
        ->post(route('store.orders.payment.pix', $order))
        ->assertForbidden();

    Http::assertNothingSent();
});

test('order detail exposes pix instructions only to its owner', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    Payment::factory()->for($order)->create([
        'pix_qr_code' => 'pix-do-pedido',
        'pix_qr_code_base64' => 'imagem-pix',
        'pix_ticket_url' => 'https://www.mercadopago.com.br/payments/order/ticket',
    ]);

    $this->actingAs($user)
        ->get(route('store.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Orders/Show')
            ->where('order.payment.status', PaymentStatus::Pending->value)
            ->where('order.payment.pix_qr_code', 'pix-do-pedido')
            ->where('order.payment.can_retry', false)
        );
});

test('gateway failures preserve the payment attempt for a safe retry', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response([
            'message' => 'temporary failure',
        ], 500),
    ]);

    $this->actingAs($user)
        ->post(route('store.orders.payment.pix', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    $payment = Payment::query()->sole();

    expect($payment)
        ->provider_order_id->toBeNull()
        ->status->toBe(PaymentStatus::Pending)
        ->idempotency_key->not->toBeEmpty();
});

test('provider payload must match local order reference and amount', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'number' => 'PED-00000020',
        'total_cents' => 5000,
    ]);
    $payload = mercadoPagoPixPayload($order);
    $payload['external_reference'] = 'PED-OUTRO';

    Http::fake([
        'https://api.mercadopago.com/v1/orders' => Http::response($payload, 201),
    ]);

    $this->actingAs($user)
        ->post(route('store.orders.payment.pix', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Payment::query()->sole()->provider_order_id)->toBeNull();
});

/**
 * @return array<string, mixed>
 */
function mercadoPagoPixPayload(Order $order): array
{
    return mercadoPagoProviderPayload(
        $order->number,
        formatTestAmount($order->total_cents),
    );
}

/**
 * @return array<string, mixed>
 */
function mercadoPagoProviderPayload(string $externalReference, string $amount): array
{
    return [
        'id' => 'ORD01TESTPAYMENT',
        'type' => 'online',
        'total_amount' => $amount,
        'external_reference' => $externalReference,
        'status' => 'action_required',
        'status_detail' => 'waiting_transfer',
        'transactions' => [
            'payments' => [[
                'id' => 'PAY01TESTPAYMENT',
                'status' => 'action_required',
                'status_detail' => 'waiting_transfer',
                'amount' => $amount,
                'payment_method' => [
                    'id' => 'pix',
                    'type' => 'bank_transfer',
                    'ticket_url' => 'https://www.mercadopago.com.br/payments/test/ticket',
                    'qr_code' => 'pix-copia-e-cola',
                    'qr_code_base64' => 'base64-qr-code',
                ],
            ]],
        ],
    ];
}

function formatTestAmount(int $amountCents): string
{
    return sprintf('%d.%02d', intdiv($amountCents, 100), $amountCents % 100);
}
