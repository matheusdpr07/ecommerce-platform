<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StorefrontCatalogService
{
    /**
     * @return array{
     *     search: string,
     *     category: string,
     *     brand: string,
     *     sort: string,
     *     min_price: string,
     *     max_price: string
     * }
     */
    public function extractFilters(Request $request, ?Category $category = null): array
    {
        return [
            'search' => $request->string('search')->trim()->toString(),
            'category' => $category?->slug ?? $request->string('category')->trim()->toString(),
            'brand' => $request->string('brand')->trim()->toString(),
            'sort' => $request->string('sort')->trim()->toString() ?: 'name',
            'min_price' => $request->string('min_price')->trim()->toString(),
            'max_price' => $request->string('max_price')->trim()->toString(),
        ];
    }

    /**
     * @param  array{
     *     search: string,
     *     category: string,
     *     brand: string,
     *     sort: string,
     *     min_price: string,
     *     max_price: string
     * }  $filters
     */
    public function paginateProducts(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $minPriceCents = $this->parsePriceToCents($filters['min_price']);
        $maxPriceCents = $this->parsePriceToCents($filters['max_price']);

        $query = Product::query()
            ->visibleInStorefront()
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'variants' => fn ($query) => $query
                    ->select('id', 'product_id', 'price_cents', 'compare_at_price_cents', 'stock_quantity', 'is_active')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'images' => fn ($query) => $query
                    ->select('id', 'product_id', 'path', 'alt_text', 'sort_order', 'is_primary')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', fn (Builder $query) => $query
                            ->where('is_active', true)
                            ->where('sku', 'like', "%{$search}%"));
                });
            })
            ->when($filters['category'] !== '', function (Builder $query) use ($filters): void {
                $query->whereHas('category', fn (Builder $query) => $query
                    ->where('slug', $filters['category'])
                    ->where('is_active', true));
            })
            ->when($filters['brand'] !== '', function (Builder $query) use ($filters): void {
                $query->whereHas('brand', fn (Builder $query) => $query
                    ->where('slug', $filters['brand'])
                    ->where('is_active', true));
            })
            ->when($minPriceCents !== null, function (Builder $query) use ($minPriceCents): void {
                $query->whereHas('variants', fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->where('price_cents', '>=', $minPriceCents));
            })
            ->when($maxPriceCents !== null, function (Builder $query) use ($maxPriceCents): void {
                $query->whereHas('variants', fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->where('price_cents', '<=', $maxPriceCents));
            });

        $this->applySort($query, $filters['sort']);

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product) => $this->transformSummary($product));
    }

    public function findVisibleProduct(string $slug): Product
    {
        return Product::query()
            ->visibleInStorefront()
            ->where('slug', $slug)
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'images' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->firstOrFail();
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function activeCategoryOptions(): array
    {
        return Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function activeBrandOptions(): array
    {
        return Brand::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function transformSummary(Product $product): array
    {
        /** @var Collection<int, ProductVariant> $variants */
        $variants = $product->variants;
        $minPrice = (int) $variants->min('price_cents');
        $maxPrice = (int) $variants->max('price_cents');
        $primaryImage = $this->resolvePrimaryImage($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
            'min_price_cents' => $minPrice,
            'max_price_cents' => $maxPrice,
            'has_stock' => $variants->sum('stock_quantity') > 0,
            'primary_image' => $primaryImage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transformDetail(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'price_cents' => $variant->price_cents,
                'compare_at_price_cents' => $variant->compare_at_price_cents,
                'stock_quantity' => $variant->stock_quantity,
                'in_stock' => $variant->stock_quantity > 0,
            ])->values()->all(),
            'images' => $product->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'url' => $image->url(),
                'alt_text' => $image->alt_text,
                'is_primary' => $image->is_primary,
            ])->values()->all(),
        ];
    }

    private function applySort(Builder $query, string $sort): void
    {
        $minPriceSubquery = ProductVariant::query()
            ->selectRaw('MIN(price_cents)')
            ->whereColumn('product_id', 'products.id')
            ->where('is_active', true);

        match ($sort) {
            'price_asc' => $query
                ->addSelect(['storefront_min_price' => $minPriceSubquery])
                ->orderBy('storefront_min_price'),
            'price_desc' => $query
                ->addSelect(['storefront_min_price' => $minPriceSubquery])
                ->orderByDesc('storefront_min_price'),
            'newest' => $query->latest(),
            default => $query->orderBy('name'),
        };
    }

    private function parsePriceToCents(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(['.', ','], ['', '.'], $value);
        $parsed = (float) $normalized;

        if ($parsed < 0) {
            return null;
        }

        return (int) round($parsed * 100);
    }

    /**
     * @return array{url: string, alt_text: string|null}|null
     */
    private function resolvePrimaryImage(Product $product): ?array
    {
        $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        if ($image === null) {
            return null;
        }

        return [
            'url' => $image->url(),
            'alt_text' => $image->alt_text,
        ];
    }
}
