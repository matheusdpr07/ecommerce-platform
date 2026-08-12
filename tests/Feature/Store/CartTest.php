<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;

test('guests can add products to cart', function () {
    $product = createStorefrontProduct();
    $variant = $product->variants->first();

    $response = $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $response->assertRedirect();

    expect(CartItem::query()->where('product_variant_id', $variant->id)->first())
        ->quantity->toBe(2);
});

test('cart page shows items for guests', function () {
    $product = createStorefrontProduct(['name' => 'Produto Carrinho']);
    $variant = $product->variants->first();

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->get(route('store.cart.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Cart/Index')
            ->has('cart.items', 1)
            ->where('cart.items.0.product.name', 'Produto Carrinho')
            ->where('cart.subtotal_cents', 5000)
        );
});

test('cart rejects quantities above stock', function () {
    $product = createStorefrontProduct([], ['stock_quantity' => 2]);
    $variant = $product->variants->first();

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ])->assertSessionHasErrors('quantity');
});

test('authenticated users can update and remove cart items', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct();
    $variant = $product->variants->first();
    $cart = Cart::factory()->create(['user_id' => $user->id]);
    $item = CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->patch(route('store.cart.items.update', $item), ['quantity' => 3])
        ->assertRedirect();

    expect($item->fresh()->quantity)->toBe(3);

    $this->actingAs($user)
        ->delete(route('store.cart.items.destroy', $item))
        ->assertRedirect();

    expect(CartItem::query()->find($item->id))->toBeNull();
});

test('guest cart merges into user cart on login', function () {
    $product = createStorefrontProduct();
    $variant = $product->variants->first();
    $user = User::factory()->create();

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $guestCart = Cart::query()->whereNotNull('session_id')->first();
    expect($guestCart)->not->toBeNull();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    expect(Cart::query()->where('session_id', $guestCart->session_id)->exists())->toBeFalse();

    $userCart = Cart::query()->where('user_id', $user->id)->first();
    expect($userCart)->not->toBeNull()
        ->and($userCart->items()->first()->quantity)->toBe(2);
});

test('users cannot modify cart items from another cart', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $product = createStorefrontProduct();
    $variant = $product->variants->first();
    $cart = Cart::factory()->create(['user_id' => $owner->id]);
    $item = CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_variant_id' => $variant->id,
    ]);

    $this->actingAs($intruder)
        ->delete(route('store.cart.items.destroy', $item))
        ->assertForbidden();
});
