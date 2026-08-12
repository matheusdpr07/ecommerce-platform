<?php

use App\Enums\DiscountType;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\User;

test('guests can apply valid coupon to cart', function () {
    $product = createStorefrontProduct([], [
        'price_cents' => 10000,
        'stock_quantity' => 5,
    ]);

    Coupon::factory()->create([
        'code' => 'DESC10',
        'type' => DiscountType::Percentage,
        'value' => 10,
    ]);

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ])->assertRedirect();

    $response = $this->post(route('store.cart.coupon.apply'), [
        'code' => 'DESC10',
    ]);

    $response->assertRedirect()
        ->assertSessionHas('success');

    $this->get(route('store.cart.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('cart.subtotal_cents', 10000)
            ->where('cart.discount_cents', 1000)
            ->where('cart.total_cents', 9000)
            ->where('cart.coupon.code', 'DESC10')
        );
});

test('cart rejects invalid coupon codes', function () {
    $product = createStorefrontProduct([], [
        'price_cents' => 5000,
        'stock_quantity' => 5,
    ]);

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->post(route('store.cart.coupon.apply'), [
        'code' => 'INVALIDO',
    ])->assertSessionHasErrors('code');
});

test('cart rejects coupon when minimum order is not met', function () {
    $product = createStorefrontProduct([], [
        'price_cents' => 3000,
        'stock_quantity' => 5,
    ]);

    Coupon::factory()->create([
        'code' => 'MIN50',
        'type' => DiscountType::FixedAmount,
        'value' => 500,
        'min_order_cents' => 5000,
    ]);

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->post(route('store.cart.coupon.apply'), [
        'code' => 'MIN50',
    ])->assertSessionHasErrors('code');
});

test('users can remove applied coupon from cart', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 8000,
        'stock_quantity' => 5,
    ]);
    $coupon = Coupon::factory()->create([
        'code' => 'REMOVE10',
        'type' => DiscountType::Percentage,
        'value' => 10,
    ]);

    $cart = Cart::factory()->for($user)->create([
        'coupon_id' => $coupon->id,
    ]);

    $cart->items()->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->delete(route('store.cart.coupon.remove'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($cart->fresh()->coupon_id)->toBeNull();
});

test('clearing cart removes applied coupon', function () {
    $product = createStorefrontProduct([], [
        'price_cents' => 4000,
        'stock_quantity' => 5,
    ]);
    $coupon = Coupon::factory()->create([
        'code' => 'CLEAR10',
        'type' => DiscountType::Percentage,
        'value' => 10,
    ]);

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->post(route('store.cart.coupon.apply'), [
        'code' => 'CLEAR10',
    ]);

    $this->delete(route('store.cart.clear'))
        ->assertRedirect();

    $cart = Cart::query()->where('coupon_id', $coupon->id)->first();

    expect($cart)->toBeNull();
});
