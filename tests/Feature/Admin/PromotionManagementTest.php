<?php

use App\Enums\DiscountType;
use App\Enums\PromotionScope;
use App\Models\Category;
use App\Models\Promotion;
use App\Models\User;

test('customers cannot manage promotions', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.promotions.index'))
        ->assertForbidden();
});

test('admins can list promotions with pagination', function () {
    $admin = User::factory()->admin()->create();
    Promotion::factory()->count(16)->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.promotions.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Promotions/Index')
            ->has('promotions.data', 15)
            ->where('promotions.total', 16)
        );
});

test('admins can create promotions', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.promotions.store'), [
            'name' => 'Promocao de verao',
            'type' => DiscountType::Percentage->value,
            'value' => 20,
            'scope' => PromotionScope::Category->value,
            'category_id' => $category->id,
            'brand_id' => null,
            'product_id' => null,
            'priority' => 10,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.promotions.index'));

    $this->assertDatabaseHas('promotions', [
        'name' => 'Promocao de verao',
        'scope' => PromotionScope::Category->value,
        'category_id' => $category->id,
    ]);
});

test('admins can update promotions', function () {
    $admin = User::factory()->admin()->create();
    $promotion = Promotion::factory()->create(['name' => 'Antiga']);

    $response = $this->actingAs($admin)
        ->put(route('admin.promotions.update', $promotion), [
            'name' => 'Promocao atualizada',
            'type' => DiscountType::FixedAmount->value,
            'value' => 500,
            'scope' => PromotionScope::AllProducts->value,
            'category_id' => null,
            'brand_id' => null,
            'product_id' => null,
            'priority' => 5,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.promotions.index'));

    expect($promotion->fresh())
        ->name->toBe('Promocao atualizada')
        ->type->toBe(DiscountType::FixedAmount)
        ->value->toBe(500);
});

test('admins can delete promotions', function () {
    $admin = User::factory()->admin()->create();
    $promotion = Promotion::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.promotions.destroy', $promotion))
        ->assertRedirect(route('admin.promotions.index'));

    $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
});

test('promotion creation requires scoped target', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.promotions.store'), [
            'name' => 'Promocao invalida',
            'type' => DiscountType::Percentage->value,
            'value' => 10,
            'scope' => PromotionScope::Product->value,
            'category_id' => null,
            'brand_id' => null,
            'product_id' => null,
            'priority' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('product_id');
});

test('storefront applies product promotion to visible prices', function () {
    $product = createStorefrontProduct([], [
        'price_cents' => 10000,
    ]);

    Promotion::factory()->forProduct($product->id)->create([
        'type' => DiscountType::Percentage,
        'value' => 10,
    ]);

    $this->get(route('store.products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.variants.0.price_cents', 9000)
            ->where('product.variants.0.original_price_cents', 10000)
            ->where('product.variants.0.has_promotion', true)
        );
});
