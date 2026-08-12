<?php

use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\User;

test('customers cannot manage coupons', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.coupons.index'))
        ->assertForbidden();
});

test('admins can list coupons with pagination', function () {
    $admin = User::factory()->admin()->create();
    Coupon::factory()->count(16)->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.coupons.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Coupons/Index')
            ->has('coupons.data', 15)
            ->where('coupons.total', 16)
        );
});

test('admins can create coupons', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.coupons.store'), [
            'code' => 'SAVE10',
            'name' => 'Desconto de lancamento',
            'type' => DiscountType::Percentage->value,
            'value' => 10,
            'min_order_cents' => null,
            'max_discount_cents' => null,
            'usage_limit' => 100,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.coupons.index'));

    $this->assertDatabaseHas('coupons', [
        'code' => 'SAVE10',
        'name' => 'Desconto de lancamento',
        'value' => 10,
    ]);
});

test('admins can update coupons', function () {
    $admin = User::factory()->admin()->create();
    $coupon = Coupon::factory()->create(['code' => 'OLD10']);

    $response = $this->actingAs($admin)
        ->put(route('admin.coupons.update', $coupon), [
            'code' => 'NEW10',
            'name' => 'Cupom atualizado',
            'type' => DiscountType::FixedAmount->value,
            'value' => 1500,
            'min_order_cents' => null,
            'max_discount_cents' => null,
            'usage_limit' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.coupons.index'));

    expect($coupon->fresh())
        ->code->toBe('NEW10')
        ->type->toBe(DiscountType::FixedAmount)
        ->value->toBe(1500);
});

test('admins can delete coupons', function () {
    $admin = User::factory()->admin()->create();
    $coupon = Coupon::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.coupons.destroy', $coupon))
        ->assertRedirect(route('admin.coupons.index'));

    $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
});

test('coupon listing supports status filter', function () {
    $admin = User::factory()->admin()->create();
    Coupon::factory()->create(['code' => 'ATIVO1', 'is_active' => true]);
    Coupon::factory()->inactive()->create(['code' => 'INATIVO1']);

    $this->actingAs($admin)
        ->get(route('admin.coupons.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('coupons.data', 1)
            ->where('coupons.data.0.code', 'INATIVO1')
        );
});
