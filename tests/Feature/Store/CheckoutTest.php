<?php

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ShippingMethod;
use App\Models\User;

test('guests are redirected from checkout', function () {
    $this->get(route('store.checkout.index'))
        ->assertRedirect(route('login', absolute: false));
});

test('checkout requires items in cart', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('store.checkout.index'))
        ->assertRedirect(route('store.cart.index'))
        ->assertSessionHasErrors('cart');
});

test('authenticated users can view checkout with shipping options', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 10000,
        'stock_quantity' => 5,
    ]);
    $address = Address::factory()->for($user)->default()->create();
    ShippingMethod::factory()->create([
        'name' => 'PAC',
        'price_cents' => 1200,
    ]);

    $cart = Cart::factory()->for($user)->create();
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('store.checkout.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Checkout/Index')
            ->where('selected_address_id', $address->id)
            ->has('shipping_methods', 1)
            ->where('cart.subtotal_cents', 10000)
        );
});

test('users can save checkout selections', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 5000,
        'stock_quantity' => 3,
    ]);
    $address = Address::factory()->for($user)->create();
    $method = ShippingMethod::factory()->create([
        'price_cents' => 800,
    ]);

    $cart = Cart::factory()->for($user)->create();
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('store.checkout.update'), [
            'shipping_address_id' => $address->id,
            'shipping_method_id' => $method->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($cart->fresh())
        ->shipping_address_id->toBe($address->id)
        ->shipping_method_id->toBe($method->id)
        ->shipping_cents->toBe(800);
});

test('checkout applies free shipping threshold', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], [
        'price_cents' => 25000,
        'stock_quantity' => 2,
    ]);
    $address = Address::factory()->for($user)->create();
    $method = ShippingMethod::factory()->create([
        'price_cents' => 1500,
        'free_above_cents' => 20000,
    ]);

    $cart = Cart::factory()->for($user)->create();
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('store.checkout.update'), [
            'shipping_address_id' => $address->id,
            'shipping_method_id' => $method->id,
        ])
        ->assertRedirect();

    expect($cart->fresh()->shipping_cents)->toBe(0);
});

test('users cannot checkout with another users address', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = createStorefrontProduct([], ['stock_quantity' => 2]);
    $foreignAddress = Address::factory()->for($otherUser)->create();
    $method = ShippingMethod::factory()->create();

    $cart = Cart::factory()->for($user)->create();
    CartItem::factory()->for($cart)->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('store.checkout.update'), [
            'shipping_address_id' => $foreignAddress->id,
            'shipping_method_id' => $method->id,
        ])
        ->assertSessionHasErrors('shipping_address_id');
});
