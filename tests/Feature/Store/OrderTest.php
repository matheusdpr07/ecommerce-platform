<?php

use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\StockMovement;
use App\Models\User;

test('guests cannot place orders', function () {
    $this->post(route('store.checkout.store'))
        ->assertRedirect(route('login', absolute: false));
});

test('guests are redirected from orders list', function () {
    $this->get(route('store.orders.index'))
        ->assertRedirect(route('login', absolute: false));
});

test('users cannot place order without checkout selections', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 5000,
        'stock_quantity' => 3,
    ]);

    $cart = Cart::factory()->for($user)->create();
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('store.checkout.store'))
        ->assertSessionHasErrors('checkout');
});

test('users can place order and stock is decremented transactionally', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 10000,
        'stock_quantity' => 5,
    ]);
    $variant = $product->variants->first();
    $address = Address::factory()->for($user)->create();
    $method = ShippingMethod::factory()->create([
        'price_cents' => 1200,
    ]);

    $cart = Cart::factory()->for($user)->create([
        'shipping_address_id' => $address->id,
        'shipping_method_id' => $method->id,
        'shipping_cents' => 1200,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->post(route('store.checkout.store'))
        ->assertRedirect()
        ->assertSessionHas('success');

    $order = Order::query()->first();

    expect($order)
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->status->toBe(OrderStatus::PendingPayment)
        ->subtotal_cents->toBe(20000)
        ->shipping_cents->toBe(1200)
        ->total_cents->toBe(21200)
        ->number->toStartWith('PED-');

    expect($order->items)->toHaveCount(1);
    expect($order->items->first())
        ->product_variant_id->toBe($variant->id)
        ->quantity->toBe(2)
        ->unit_price_cents->toBe(10000)
        ->line_total_cents->toBe(20000);

    expect($variant->fresh()->stock_quantity)->toBe(3);

    expect(StockMovement::query()->where('product_variant_id', $variant->id)->first())
        ->reason->toBe(StockMovementReason::Sale)
        ->quantity_change->toBe(-2)
        ->quantity_after->toBe(3);

    expect($cart->fresh()->items)->toHaveCount(0);
});

test('placing order increments coupon usage count', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 8000,
        'stock_quantity' => 4,
    ]);
    $address = Address::factory()->for($user)->create();
    $method = ShippingMethod::factory()->create([
        'price_cents' => 0,
        'free_above_cents' => 5000,
    ]);
    $coupon = Coupon::factory()->create([
        'code' => 'SAVE10',
        'type' => 'percentage',
        'value' => 10,
        'usage_limit' => 5,
        'usage_count' => 0,
    ]);

    $cart = Cart::factory()->for($user)->create([
        'coupon_id' => $coupon->id,
        'shipping_address_id' => $address->id,
        'shipping_method_id' => $method->id,
        'shipping_cents' => 0,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('store.checkout.store'))
        ->assertRedirect();

    expect($coupon->fresh()->usage_count)->toBe(1);

    $order = Order::query()->first();

    expect($order)
        ->discount_cents->toBe(800)
        ->total_cents->toBe(7200)
        ->coupon_code->toBe('SAVE10');
});

test('users cannot place order when stock is insufficient', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 5000,
        'stock_quantity' => 1,
    ]);
    $variant = $product->variants->first();
    $address = Address::factory()->for($user)->create();
    $method = ShippingMethod::factory()->create();

    $cart = Cart::factory()->for($user)->create([
        'shipping_address_id' => $address->id,
        'shipping_method_id' => $method->id,
        'shipping_cents' => $method->price_cents,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $this->actingAs($user)
        ->post(route('store.checkout.store'))
        ->assertSessionHasErrors('cart');

    expect(Order::query()->count())->toBe(0);
    expect($variant->fresh()->stock_quantity)->toBe(1);
});

test('users can view their orders', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'number' => 'PED-00000001',
        'total_cents' => 15000,
    ]);

    $this->actingAs($user)
        ->get(route('store.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Orders/Index')
            ->has('orders', 1)
            ->where('orders.0.number', 'PED-00000001')
        );

    $this->actingAs($user)
        ->get(route('store.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Orders/Show')
            ->where('order.number', 'PED-00000001')
            ->where('order.total_cents', 15000)
        );
});

test('users cannot view another users order', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $order = Order::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('store.orders.show', $order))
        ->assertForbidden();
});
