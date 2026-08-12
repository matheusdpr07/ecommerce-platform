<?php

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\URL;

test('guest customers return to checkout after login', function () {
    $product = createStorefrontProduct();
    $variant = $product->variants->first();
    $user = User::factory()->create();

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $this->get(route('store.checkout.index'))
        ->assertRedirect(route('login', absolute: false));

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->where('checkoutIntent', true)
        );

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('store.checkout.index'));

    expect($user->fresh()->cart)
        ->not->toBeNull()
        ->and($user->fresh()->cart->items()->first()->quantity)->toBe(2);
});

test('unverified customers keep checkout intent until email verification', function () {
    $product = createStorefrontProduct();
    $variant = $product->variants->first();
    $user = User::factory()->unverified()->create();

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->get(route('store.checkout.index'))
        ->assertRedirect(route('login', absolute: false));

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('verification.notice'));

    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/VerifyEmail')
            ->where('checkoutIntent', true)
        );

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(5),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->get($verificationUrl)
        ->assertRedirect(route('store.checkout.index'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('new accounts created during checkout resume purchase after verification', function () {
    $product = createStorefrontProduct();
    $variant = $product->variants->first();

    $this->post(route('store.cart.items.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->get(route('store.checkout.index'))
        ->assertRedirect(route('login', absolute: false));

    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->where('checkoutIntent', true)
        );

    $this->post(route('register'), [
        'name' => 'Cliente Checkout',
        'email' => 'checkout@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'checkout@example.com')->sole();

    expect(Cart::query()->where('user_id', $user->id)->first())
        ->not->toBeNull()
        ->and($user->cart->items)->toHaveCount(1);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(5),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->get($verificationUrl)
        ->assertRedirect(route('store.checkout.index'));
});

test('customers with saved cart resume checkout after verifying in a new session', function () {
    $user = User::factory()->unverified()->create();
    $product = createStorefrontProduct();
    $cart = Cart::factory()->for($user)->create();
    $cart->items()->create([
        'product_variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(5),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('store.checkout.index', absolute: false));
});
