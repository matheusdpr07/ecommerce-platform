<?php

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;

test('guests cannot access wishlist page', function () {
    $this->get(route('store.wishlist.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can add products to wishlist', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct(['name' => 'Produto Favorito']);

    $response = $this->actingAs($user)
        ->post(route('store.wishlist.items.store'), [
            'product_id' => $product->id,
        ]);

    $response->assertRedirect();

    expect(WishlistItem::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('wishlist page lists saved products', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct(['name' => 'Relogio Smart']);
    WishlistItem::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($user)
        ->get(route('store.wishlist.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Wishlist/Index')
            ->has('wishlist.items', 1)
            ->where('wishlist.items.0.product.name', 'Relogio Smart')
        );
});

test('users can move wishlist items to cart', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct([], ['stock_quantity' => 5]);
    $item = WishlistItem::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($user)
        ->post(route('store.wishlist.items.move-to-cart', $item))
        ->assertRedirect(route('store.cart.index'));

    expect(WishlistItem::query()->find($item->id))->toBeNull()
        ->and($user->cart?->items)->toHaveCount(1);
});

test('users cannot manage another users wishlist item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $product = Product::factory()->withVariant()->create();
    $item = WishlistItem::factory()->create([
        'user_id' => $owner->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($intruder)
        ->delete(route('store.wishlist.items.destroy', $item))
        ->assertForbidden();
});

test('product page indicates wishlist state for authenticated users', function () {
    $user = User::factory()->create();
    $product = createStorefrontProduct(['slug' => 'produto-favorito']);

    WishlistItem::factory()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $this->actingAs($user)
        ->get(route('store.products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('is_in_wishlist', true)
        );
});
