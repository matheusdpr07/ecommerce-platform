<?php

use App\Models\Brand;
use App\Models\User;

test('customers cannot manage brands', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.brands.index'))
        ->assertForbidden();
});

test('admins can list brands with pagination', function () {
    $admin = User::factory()->admin()->create();
    Brand::factory()->count(16)->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.brands.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Brands/Index')
            ->has('brands.data', 15)
            ->where('brands.total', 16)
        );
});

test('admins can create brands', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.brands.store'), [
            'name' => 'Nike',
            'slug' => 'nike',
            'description' => 'Marca esportiva',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.brands.index'));

    $this->assertDatabaseHas('brands', [
        'name' => 'Nike',
        'slug' => 'nike',
    ]);
});

test('admins can update brands', function () {
    $admin = User::factory()->admin()->create();
    $brand = Brand::factory()->create(['name' => 'Adidas']);

    $response = $this->actingAs($admin)
        ->put(route('admin.brands.update', $brand), [
            'name' => 'Adidas Originals',
            'slug' => 'adidas-originals',
            'description' => null,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.brands.index'));

    expect($brand->fresh()->name)->toBe('Adidas Originals');
});

test('admins can delete brands', function () {
    $admin = User::factory()->admin()->create();
    $brand = Brand::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.brands.destroy', $brand))
        ->assertRedirect(route('admin.brands.index'));

    $this->assertSoftDeleted('brands', ['id' => $brand->id]);
});

test('brand listing supports status filter', function () {
    $admin = User::factory()->admin()->create();
    Brand::factory()->create(['name' => 'Ativa Marca', 'is_active' => true]);
    Brand::factory()->inactive()->create(['name' => 'Inativa Marca']);

    $this->actingAs($admin)
        ->get(route('admin.brands.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('brands.data', 1)
            ->where('brands.data.0.name', 'Inativa Marca')
        );
});
