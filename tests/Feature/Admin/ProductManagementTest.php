<?php

use App\Enums\StockMovementReason;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('customers cannot manage products', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.products.index'))
        ->assertForbidden();
});

test('admins can list products with pagination', function () {
    $admin = User::factory()->admin()->create();
    Product::factory()->withVariant()->count(16)->create();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Products/Index')
            ->has('products.data', 15)
            ->where('products.total', 16)
        );
});

test('admins can create products with variants and images', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'name' => 'Camiseta Basica',
            'slug' => 'camiseta-basica',
            'description' => 'Camiseta de algodao',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
            'variants' => [
                [
                    'sku' => 'CAM-P',
                    'name' => 'Pequeno',
                    'price_cents' => 4990,
                    'compare_at_price_cents' => null,
                    'stock_quantity' => 10,
                    'is_active' => true,
                    'sort_order' => 0,
                ],
                [
                    'sku' => 'CAM-M',
                    'name' => 'Medio',
                    'price_cents' => 4990,
                    'compare_at_price_cents' => 5990,
                    'stock_quantity' => 5,
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            ],
            'images' => [
                UploadedFile::fake()->image('produto.jpg'),
            ],
        ]);

    $response->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('slug', 'camiseta-basica')->first();

    expect($product)->not->toBeNull()
        ->and($product->variants)->toHaveCount(2)
        ->and($product->images)->toHaveCount(1);

    $this->assertDatabaseHas('product_variants', [
        'product_id' => $product->id,
        'sku' => 'CAM-P',
        'price_cents' => 4990,
        'stock_quantity' => 10,
    ]);

    expect(
        StockMovement::query()
            ->where('product_variant_id', $product->variants->first()->id)
            ->where('reason', StockMovementReason::Initial)
            ->exists()
    )->toBeTrue();

    Storage::disk('public')->assertExists($product->images->first()->path);
});

test('admins can update products and record stock adjustments', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->withVariant([
        'sku' => 'SKU-OLD',
        'stock_quantity' => 5,
    ])->create();
    $variant = $product->variants->first();
    $newCategory = Category::factory()->create();

    $response = $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'name' => 'Produto Atualizado',
            'slug' => 'produto-atualizado',
            'description' => 'Descricao atualizada',
            'category_id' => $newCategory->id,
            'brand_id' => null,
            'is_active' => false,
            'variants' => [
                [
                    'id' => $variant->id,
                    'sku' => 'SKU-NEW',
                    'name' => 'Unico',
                    'price_cents' => 9900,
                    'compare_at_price_cents' => null,
                    'stock_quantity' => 12,
                    'is_active' => true,
                    'sort_order' => 0,
                ],
            ],
        ]);

    $response->assertRedirect(route('admin.products.index'));

    $variant->refresh();

    expect($product->fresh())
        ->name->toBe('Produto Atualizado')
        ->is_active->toBeFalse()
        ->category_id->toBe($newCategory->id)
        ->and($variant)
        ->sku->toBe('SKU-NEW')
        ->stock_quantity->toBe(12);

    expect(
        StockMovement::query()
            ->where('product_variant_id', $variant->id)
            ->where('reason', StockMovementReason::ManualAdjustment)
            ->where('quantity_change', 7)
            ->exists()
    )->toBeTrue();
});

test('admins can delete products', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $product = Product::factory()->withVariant()->create();
    $path = 'products/'.$product->id.'/test.jpg';
    Storage::disk('public')->put($path, 'conteudo');
    $product->images()->create([
        'path' => $path,
        'alt_text' => 'Teste',
        'sort_order' => 0,
        'is_primary' => true,
    ]);

    $response = $this->actingAs($admin)
        ->delete(route('admin.products.destroy', $product));

    $response->assertRedirect(route('admin.products.index'));

    expect(Product::withTrashed()->find($product->id)?->trashed())->toBeTrue();
    Storage::disk('public')->assertMissing($path);
});

test('product listing supports search by sku', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->withVariant(['sku' => 'BUSCA-123'])->create(['name' => 'Item A']);
    Product::factory()->withVariant(['sku' => 'OUTRO-999'])->create(['name' => 'Item B']);

    $this->actingAs($admin)
        ->get(route('admin.products.index', ['search' => 'BUSCA-123']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $product->id)
        );
});

test('products require at least one variant', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'name' => 'Sem variacao',
            'category_id' => $category->id,
            'is_active' => true,
            'variants' => [],
        ]);

    $response->assertSessionHasErrors('variants');
});

test('admins cannot delete categories with products', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    Product::factory()->withVariant()->create(['category_id' => $category->id]);

    $response = $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category));

    $response->assertRedirect()
        ->assertSessionHas('error');

    expect(Category::query()->find($category->id))->not->toBeNull();
});

test('variant sku must be unique', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    ProductVariant::factory()->create(['sku' => 'SKU-DUP']);

    $response = $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'name' => 'Produto duplicado',
            'category_id' => $category->id,
            'is_active' => true,
            'variants' => [
                [
                    'sku' => 'SKU-DUP',
                    'name' => 'Padrao',
                    'price_cents' => 1000,
                    'stock_quantity' => 1,
                    'is_active' => true,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('variants.0.sku');
});
