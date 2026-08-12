<?php

namespace App\Services;

use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductCatalogService
{
    /**
     * @param  list<array<string, mixed>>  $variantsData
     */
    public function syncVariants(Product $product, array $variantsData, ?User $user = null): void
    {
        $existingIds = $product->variants()->pluck('id');
        $submittedIds = collect($variantsData)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        $idsToDelete = $existingIds->diff($submittedIds);

        if ($idsToDelete->isNotEmpty()) {
            ProductVariant::query()->whereIn('id', $idsToDelete)->delete();
        }

        foreach ($variantsData as $index => $variantData) {
            $payload = [
                'sku' => $variantData['sku'],
                'name' => $variantData['name'],
                'price_cents' => (int) $variantData['price_cents'],
                'compare_at_price_cents' => isset($variantData['compare_at_price_cents'])
                    ? (int) $variantData['compare_at_price_cents']
                    : null,
                'stock_quantity' => (int) $variantData['stock_quantity'],
                'is_active' => (bool) $variantData['is_active'],
                'sort_order' => (int) ($variantData['sort_order'] ?? $index),
            ];

            if (! empty($variantData['id'])) {
                $variant = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->findOrFail($variantData['id']);

                $previousStock = $variant->stock_quantity;
                $variant->update($payload);

                if ($previousStock !== $payload['stock_quantity']) {
                    $this->recordStockMovement(
                        $variant,
                        $payload['stock_quantity'] - $previousStock,
                        $payload['stock_quantity'],
                        StockMovementReason::ManualAdjustment,
                        $user,
                    );
                }

                continue;
            }

            $variant = $product->variants()->create($payload);

            if ($payload['stock_quantity'] > 0) {
                $this->recordStockMovement(
                    $variant,
                    $payload['stock_quantity'],
                    $payload['stock_quantity'],
                    StockMovementReason::Initial,
                    $user,
                );
            }
        }
    }

    /**
     * @param  list<UploadedFile>|null  $newImages
     * @param  list<int>|null  $removeImageIds
     */
    public function syncImages(
        Product $product,
        ?array $newImages = null,
        ?array $removeImageIds = null,
    ): void {
        if ($removeImageIds !== null && $removeImageIds !== []) {
            ProductImage::query()
                ->where('product_id', $product->id)
                ->whereIn('id', $removeImageIds)
                ->each(fn (ProductImage $image) => $image->delete());
        }

        if ($newImages === null || $newImages === []) {
            $this->ensurePrimaryImage($product);

            return;
        }

        $currentMaxSort = (int) $product->images()->max('sort_order');
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($newImages as $index => $image) {
            if (! $image instanceof UploadedFile) {
                continue;
            }

            $path = $image->store('products/'.$product->id, 'public');

            $product->images()->create([
                'path' => $path,
                'alt_text' => $product->name,
                'sort_order' => $currentMaxSort + $index + 1,
                'is_primary' => ! $hasPrimary && $index === 0,
            ]);

            $hasPrimary = true;
        }

        $this->ensurePrimaryImage($product);
    }

    public function recordStockMovement(
        ProductVariant $variant,
        int $quantityChange,
        int $quantityAfter,
        StockMovementReason $reason,
        ?User $user = null,
        ?string $notes = null,
    ): void {
        $variant->stockMovements()->create([
            'user_id' => $user?->id,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityAfter,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $productData
     * @param  list<array<string, mixed>>  $variantsData
     * @param  list<UploadedFile>|null  $newImages
     */
    public function createProduct(array $productData, array $variantsData, ?array $newImages, User $user): Product
    {
        return DB::transaction(function () use ($productData, $variantsData, $newImages, $user): Product {
            $product = Product::query()->create($productData);
            $this->syncVariants($product, $variantsData, $user);
            $this->syncImages($product, $newImages);

            return $product->load(['variants', 'images', 'category:id,name', 'brand:id,name']);
        });
    }

    /**
     * @param  array<string, mixed>  $productData
     * @param  list<array<string, mixed>>  $variantsData
     * @param  list<UploadedFile>|null  $newImages
     * @param  list<int>|null  $removeImageIds
     */
    public function updateProduct(
        Product $product,
        array $productData,
        array $variantsData,
        ?array $newImages,
        ?array $removeImageIds,
        User $user,
    ): Product {
        return DB::transaction(function () use ($product, $productData, $variantsData, $newImages, $removeImageIds, $user): Product {
            $product->update($productData);
            $this->syncVariants($product, $variantsData, $user);
            $this->syncImages($product, $newImages, $removeImageIds);

            return $product->load(['variants', 'images', 'category:id,name', 'brand:id,name']);
        });
    }

    public function deleteProduct(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->load('images');

            $product->images->each(function (ProductImage $image): void {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            });

            $product->delete();
        });
    }

    private function ensurePrimaryImage(Product $product): void
    {
        $images = $product->images()->orderBy('sort_order')->orderBy('id')->get();

        if ($images->isEmpty()) {
            return;
        }

        $primary = $images->firstWhere('is_primary', true) ?? $images->first();

        /** @var Collection<int, ProductImage> $images */
        foreach ($images as $image) {
            $image->update(['is_primary' => $image->id === $primary->id]);
        }
    }
}
