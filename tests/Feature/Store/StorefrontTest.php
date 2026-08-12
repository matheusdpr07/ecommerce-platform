<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

test('store home is accessible without authentication', function () {
    $this->get(route('store.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Store/Products/Index'));
});

test('store home lists only visible products', function () {
    $category = Category::factory()->create(['is_active' => true, 'name' => 'Moda']);
    $visible = Product::factory()->withVariant([
        'is_active' => true,
        'stock_quantity' => 3,
        'price_cents' => 5000,
    ])->create([
        'name' => 'Camiseta Visivel',
        'slug' => 'camiseta-visivel',
        'is_active' => true,
        'category_id' => $category->id,
    ]);

    Product::factory()->withVariant()->inactive()->create([
        'name' => 'Produto Inativo',
        'category_id' => $category->id,
    ]);

    $inactiveCategory = Category::factory()->inactive()->create();
    Product::factory()->withVariant()->create([
        'name' => 'Produto Categoria Inativa',
        'category_id' => $inactiveCategory->id,
        'is_active' => true,
    ]);

    $this->get(route('store.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.slug', $visible->slug)
        );
});

test('store supports search filter', function () {
    $category = Category::factory()->create(['is_active' => true]);

    Product::factory()->withVariant(['sku' => 'BUSCA-001'])->create([
        'name' => 'Tenis Runner',
        'slug' => 'tenis-runner',
        'is_active' => true,
        'category_id' => $category->id,
    ]);

    Product::factory()->withVariant()->create([
        'name' => 'Bone Casual',
        'slug' => 'bone-casual',
        'is_active' => true,
        'category_id' => $category->id,
    ]);

    $this->get(route('store.home', ['search' => 'Tenis']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.slug', 'tenis-runner')
        );
});

test('store supports category and brand filters', function () {
    $moda = Category::factory()->create(['name' => 'Moda', 'slug' => 'moda', 'is_active' => true]);
    $tech = Category::factory()->create(['name' => 'Tech', 'slug' => 'tech', 'is_active' => true]);
    $nike = Brand::factory()->create(['name' => 'Nike', 'slug' => 'nike', 'is_active' => true]);
    $adidas = Brand::factory()->create(['name' => 'Adidas', 'slug' => 'adidas', 'is_active' => true]);

    Product::factory()->withVariant()->create([
        'name' => 'Produto Nike Moda',
        'slug' => 'produto-nike-moda',
        'category_id' => $moda->id,
        'brand_id' => $nike->id,
        'is_active' => true,
    ]);

    Product::factory()->withVariant()->create([
        'name' => 'Produto Adidas Tech',
        'slug' => 'produto-adidas-tech',
        'category_id' => $tech->id,
        'brand_id' => $adidas->id,
        'is_active' => true,
    ]);

    $this->get(route('store.home', ['category' => 'moda', 'brand' => 'nike']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.slug', 'produto-nike-moda')
        );
});

test('category page shows products from selected category', function () {
    $category = Category::factory()->create([
        'name' => 'Calcados',
        'slug' => 'calcados',
        'is_active' => true,
    ]);

    Product::factory()->withVariant()->create([
        'name' => 'Sapato Social',
        'slug' => 'sapato-social',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $this->get(route('store.categories.show', $category))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Products/Index')
            ->where('activeCategory.slug', 'calcados')
            ->has('products.data', 1)
        );
});

test('inactive categories return not found on storefront', function () {
    $category = Category::factory()->inactive()->create(['slug' => 'inativa']);

    $this->get(route('store.categories.show', $category))
        ->assertNotFound();
});

test('product page shows visible product details', function () {
    $category = Category::factory()->create(['is_active' => true, 'slug' => 'moda']);
    $brand = Brand::factory()->create(['is_active' => true, 'name' => 'Marca X', 'slug' => 'marca-x']);

    $product = Product::factory()->withVariant([
        'name' => 'Unico',
        'sku' => 'SKU-001',
        'price_cents' => 9900,
        'compare_at_price_cents' => 12900,
        'stock_quantity' => 8,
        'is_active' => true,
    ])->create([
        'name' => 'Jaqueta Premium',
        'slug' => 'jaqueta-premium',
        'description' => 'Jaqueta impermeavel.',
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'is_active' => true,
    ]);

    $this->get(route('store.products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Products/Show')
            ->where('product.slug', 'jaqueta-premium')
            ->where('product.name', 'Jaqueta Premium')
            ->where('is_in_wishlist', false)
            ->has('product.variants', 1)
            ->where('product.variants.0.price_cents', 9900)
        );
});

test('product page returns not found for invisible products', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->withVariant()->inactive()->create([
        'slug' => 'produto-oculto',
        'category_id' => $category->id,
    ]);

    $this->get(route('store.products.show', $product->slug))
        ->assertNotFound();
});

test('store home paginates products', function () {
    $category = Category::factory()->create(['is_active' => true]);
    Product::factory()->withVariant()->count(13)->create([
        'is_active' => true,
        'category_id' => $category->id,
    ]);

    $this->get(route('store.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 12)
            ->where('products.total', 13)
        );
});
