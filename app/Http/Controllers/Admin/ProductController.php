<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductCatalogService $catalogService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $categoryId = $request->integer('category_id') ?: null;
        $brandId = $request->integer('brand_id') ?: null;

        $products = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'variants:id,product_id,sku,price_cents,stock_quantity,is_active',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', fn ($query) => $query->where('sku', 'like', "%{$search}%"));
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($brandId, fn ($query) => $query->where('brand_id', $brandId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category_id' => $categoryId ? (string) $categoryId : '',
                'brand_id' => $brandId ? (string) $brandId : '',
            ],
            'categories' => $this->categoryOptions(),
            'brands' => $this->brandOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Admin/Products/Create', [
            'categories' => $this->categoryOptions(),
            'brands' => $this->brandOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $variants = $data['variants'];
        $images = $request->file('images', []);

        unset($data['variants'], $data['images']);

        $data['slug'] = $data['slug'] ?: Product::generateUniqueSlug($data['name']);
        $data['brand_id'] = $data['brand_id'] ?? null;

        $this->catalogService->createProduct(
            $data,
            $variants,
            $images === [] ? null : $images,
            $request->user(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto criado com sucesso.');
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load([
            'category:id,name',
            'brand:id,name',
            'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('name'),
            'images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        return Inertia::render('Admin/Products/Edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'is_active' => $product->is_active,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'category' => $product->category,
                'brand' => $product->brand,
                'variants' => $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'price_cents' => $variant->price_cents,
                    'compare_at_price_cents' => $variant->compare_at_price_cents,
                    'stock_quantity' => $variant->stock_quantity,
                    'is_active' => $variant->is_active,
                    'sort_order' => $variant->sort_order,
                ])->values(),
                'images' => $product->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => $image->url(),
                    'alt_text' => $image->alt_text,
                    'sort_order' => $image->sort_order,
                    'is_primary' => $image->is_primary,
                ])->values(),
            ],
            'categories' => $this->categoryOptions(),
            'brands' => $this->brandOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $variants = $data['variants'];
        $images = $request->file('images', []);
        $removeImageIds = $data['remove_image_ids'] ?? null;

        unset($data['variants'], $data['images'], $data['remove_image_ids']);

        $data['slug'] = $data['slug'] ?: Product::generateUniqueSlug($data['name'], $product->id);
        $data['brand_id'] = $data['brand_id'] ?? null;

        $this->catalogService->updateProduct(
            $product,
            $data,
            $variants,
            $images === [] ? null : $images,
            $removeImageIds,
            $request->user(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->catalogService->deleteProduct($product);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produto excluido com sucesso.');
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function brandOptions(): array
    {
        return Brand::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
            ])
            ->all();
    }
}
