<?php

use App\Models\ShippingMethod;
use App\Models\User;

test('customers cannot manage shipping methods', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.shipping-methods.index'))
        ->assertForbidden();
});

test('admins can list shipping methods with pagination', function () {
    $admin = User::factory()->admin()->create();
    ShippingMethod::factory()->count(16)->create();

    $this->actingAs($admin)
        ->get(route('admin.shipping-methods.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/ShippingMethods/Index')
            ->has('shippingMethods.data', 15)
            ->where('shippingMethods.total', 16)
        );
});

test('admins can create shipping methods', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.shipping-methods.store'), [
            'name' => 'PAC',
            'description' => 'Entrega economica',
            'price_cents' => 1500,
            'free_above_cents' => 20000,
            'min_order_cents' => null,
            'max_order_cents' => null,
            'estimated_days_min' => 5,
            'estimated_days_max' => 10,
            'sort_order' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.shipping-methods.index'));

    $this->assertDatabaseHas('shipping_methods', [
        'name' => 'PAC',
        'price_cents' => 1500,
        'free_above_cents' => 20000,
    ]);
});

test('admins can update shipping methods', function () {
    $admin = User::factory()->admin()->create();
    $method = ShippingMethod::factory()->create(['name' => 'SEDEX']);

    $this->actingAs($admin)
        ->put(route('admin.shipping-methods.update', $method), [
            'name' => 'SEDEX Expresso',
            'description' => null,
            'price_cents' => 2500,
            'free_above_cents' => null,
            'min_order_cents' => null,
            'max_order_cents' => null,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.shipping-methods.index'));

    expect($method->fresh()->name)->toBe('SEDEX Expresso');
});

test('admins can delete shipping methods', function () {
    $admin = User::factory()->admin()->create();
    $method = ShippingMethod::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.shipping-methods.destroy', $method))
        ->assertRedirect(route('admin.shipping-methods.index'));

    $this->assertDatabaseMissing('shipping_methods', ['id' => $method->id]);
});
