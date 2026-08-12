<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly PromotionService $promotionService,
    ) {}

    public function addItem(User $user, int $productId): WishlistItem
    {
        $product = Product::query()
            ->visibleInStorefront()
            ->whereKey($productId)
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => 'Este produto nao esta disponivel.',
            ]);
        }

        return WishlistItem::query()->firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ])->load(['product.category', 'product.brand', 'product.images', 'product.variants']);
    }

    public function removeItem(WishlistItem $item): void
    {
        $item->delete();
    }

    public function moveItemToCart(WishlistItem $item, int $quantity = 1): void
    {
        $product = Product::query()
            ->visibleInStorefront()
            ->whereKey($item->product_id)
            ->with(['variants' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product' => 'Este produto nao esta mais disponivel.',
            ]);
        }

        $variant = $product->variants->first(fn ($variant) => $variant->stock_quantity >= $quantity);

        if ($variant === null) {
            throw ValidationException::withMessages([
                'product' => 'Nenhuma variacao disponivel em estoque.',
            ]);
        }

        $this->cartService->addItemForUser($item->user()->firstOrFail(), $variant->id, $quantity);
        $item->delete();
    }

    /**
     * @return array{item_count: int, items: list<array<string, mixed>>}
     */
    public function getPayload(User $user): array
    {
        $items = WishlistItem::query()
            ->where('user_id', $user->id)
            ->with([
                'product.category',
                'product.brand',
                'product.images',
                'product.variants' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ])
            ->latest()
            ->get()
            ->map(fn (WishlistItem $item) => $this->transformItem($item))
            ->values()
            ->all();

        return [
            'item_count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @return array{item_count: int}
     */
    public function getSummary(User $user): array
    {
        return [
            'item_count' => WishlistItem::query()->where('user_id', $user->id)->count(),
        ];
    }

    public function isProductInWishlist(User $user, int $productId): bool
    {
        return WishlistItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function transformItem(WishlistItem $item): array
    {
        $product = $item->product;
        $activeVariants = $product->variants;
        $minPrice = (int) $activeVariants
            ->map(fn ($variant) => $this->promotionService->resolveVariantPricing($variant, $product)['price_cents'])
            ->min();
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id' => $item->id,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'min_price_cents' => $minPrice,
                'has_stock' => $activeVariants->sum('stock_quantity') > 0,
                'primary_image' => $primaryImage ? [
                    'url' => $primaryImage->url(),
                    'alt_text' => $primaryImage->alt_text,
                ] : null,
                'category' => $product->category ? [
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
            ],
        ];
    }
}
