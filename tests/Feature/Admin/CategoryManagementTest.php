<?php

use App\Models\Category;
use App\Models\User;

test('customers cannot manage categories', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});

test('admins can list categories with pagination', function () {
    $admin = User::factory()->admin()->create();
    Category::factory()->count(16)->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.categories.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Categories/Index')
            ->has('categories.data', 15)
            ->where('categories.total', 16)
        );
});

test('admins can create categories', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Eletronicos',
            'slug' => 'eletronicos',
            'description' => 'Produtos eletronicos',
            'parent_id' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

    $response->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseHas('categories', [
        'name' => 'Eletronicos',
        'slug' => 'eletronicos',
        'is_active' => true,
    ]);
});

test('admins can update categories', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create(['name' => 'Roupas']);

    $response = $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), [
            'name' => 'Moda',
            'slug' => 'moda',
            'description' => null,
            'parent_id' => null,
            'is_active' => false,
            'sort_order' => 0,
        ]);

    $response->assertRedirect(route('admin.categories.index'));

    expect($category->fresh())
        ->name->toBe('Moda')
        ->is_active->toBeFalse();
});

test('admins cannot delete categories with children', function () {
    $admin = User::factory()->admin()->create();
    $parent = Category::factory()->create();
    Category::factory()->withParent($parent)->create();

    $response = $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $parent));

    $response->assertRedirect()
        ->assertSessionHas('error');

    expect(Category::query()->find($parent->id))->not->toBeNull();
});

test('category listing supports search filter', function () {
    $admin = User::factory()->admin()->create();
    Category::factory()->create(['name' => 'Calcados']);
    Category::factory()->create(['name' => 'Eletronicos']);

    $this->actingAs($admin)
        ->get(route('admin.categories.index', ['search' => 'Calcados']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Calcados')
        );
});
