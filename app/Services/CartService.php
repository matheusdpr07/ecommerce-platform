<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private readonly PromotionService $promotionService,
        private readonly CouponService $couponService,
    ) {}

    public function resolveCart(Request $request): Cart
    {
        if ($user = $request->user()) {
            return Cart::query()->firstOrCreate(['user_id' => $user->id]);
        }

        $sessionId = $request->session()->get('cart_session_id');

        if (! $sessionId) {
            $sessionId = (string) Str::uuid();
            $request->session()->put('cart_session_id', $sessionId);
        }

        return Cart::query()->firstOrCreate(
            ['session_id' => $sessionId],
            ['user_id' => null],
        );
    }

    public function mergeGuestCartForRequest(Request $request, User $user): void
    {
        $sessionId = $request->session()->get('cart_session_id');

        if (! $sessionId) {
            return;
        }

        $guestCart = Cart::query()
            ->where('session_id', $sessionId)
            ->whereNull('user_id')
            ->first();

        if ($guestCart === null) {
            return;
        }

        DB::transaction(function () use ($guestCart, $user, $request): void {
            $userCart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

            foreach ($guestCart->items()->get() as $guestItem) {
                $existingItem = $userCart->items()
                    ->where('product_variant_id', $guestItem->product_variant_id)
                    ->first();

                if ($existingItem !== null) {
                    $existingItem->update([
                        'quantity' => $existingItem->quantity + $guestItem->quantity,
                    ]);

                    continue;
                }

                $userCart->items()->create([
                    'product_variant_id' => $guestItem->product_variant_id,
                    'quantity' => $guestItem->quantity,
                ]);
            }

            if ($guestCart->coupon_id !== null && $userCart->coupon_id === null) {
                $userCart->update(['coupon_id' => $guestCart->coupon_id]);
            }

            $guestCart->delete();
            $request->session()->forget('cart_session_id');
        });
    }

    public function addItem(Request $request, int $variantId, int $quantity): CartItem
    {
        return $this->addItemToCart($this->resolveCart($request), $variantId, $quantity);
    }

    public function addItemForUser(User $user, int $variantId, int $quantity): CartItem
    {
        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        return $this->addItemToCart($cart, $variantId, $quantity);
    }

    public function addItemToCart(Cart $cart, int $variantId, int $quantity): CartItem
    {
        $variant = $this->findPurchasableVariant($variantId);
        $this->assertQuantityAvailable($variant, $quantity);

        $item = $cart->items()
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($item !== null) {
            $newQuantity = $item->quantity + $quantity;
            $this->assertQuantityAvailable($variant, $newQuantity);
            $item->update(['quantity' => $newQuantity]);

            return $item->fresh(['variant.product.images', 'variant.product.category']);
        }

        return $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ])->load(['variant.product.images', 'variant.product.category']);
    }

    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        $variant = $this->findPurchasableVariant($item->product_variant_id);
        $this->assertQuantityAvailable($variant, $quantity);

        $item->update(['quantity' => $quantity]);

        return $item->fresh(['variant.product.images', 'variant.product.category']);
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
        $this->couponService->removeFromCart($cart);
        $cart->update([
            'shipping_address_id' => null,
            'shipping_method_id' => null,
            'shipping_cents' => null,
        ]);
    }

    public function applyCoupon(Request $request, string $code): void
    {
        $cart = $this->resolveCart($request);
        $subtotal = $this->calculateSubtotalForCart($cart);
        $this->couponService->applyToCart($cart, $code, $subtotal);
    }

    public function removeCoupon(Request $request): void
    {
        $cart = $this->resolveCart($request);
        $this->couponService->removeFromCart($cart);
    }

    public function cartBelongsToRequest(Cart $cart, Request $request): bool
    {
        if ($user = $request->user()) {
            return $cart->user_id === $user->id;
        }

        return $cart->session_id === $request->session()->get('cart_session_id');
    }

    /**
     * @return array{
     *     item_count: int,
     *     subtotal_cents: int,
     *     discount_cents: int,
     *     total_cents: int,
     *     coupon: array{code: string, name: string, discount_cents: int}|null,
     *     items: list<array<string, mixed>>
     * }
     */
    public function getCartPayload(Request $request): array
    {
        $cart = $this->resolveCart($request);
        $cart->load([
            'coupon',
            'items.variant.product.category',
            'items.variant.product.brand',
            'items.variant.product.images',
        ]);

        $items = $cart->items->map(fn (CartItem $item) => $this->transformItem($item))->values()->all();
        $subtotal = (int) collect($items)->sum('line_total_cents');
        $coupon = $this->couponService->resolveCartCoupon($cart, $subtotal);
        $discount = $coupon['discount_cents'] ?? 0;

        return [
            'item_count' => (int) collect($items)->sum('quantity'),
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents' => max($subtotal - $discount, 0),
            'coupon' => $coupon,
            'items' => $items,
        ];
    }

    /**
     * @return array{item_count: int, subtotal_cents: int}
     */
    public function getSummary(Request $request): array
    {
        $payload = $this->getCartPayload($request);

        return [
            'item_count' => $payload['item_count'],
            'subtotal_cents' => $payload['subtotal_cents'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transformItem(CartItem $item): array
    {
        $variant = $item->variant;
        $product = $variant->product;
        $pricing = $this->promotionService->resolveVariantPricing($variant, $product);
        $unitPrice = $pricing['price_cents'];
        $isAvailable = $variant->is_active
            && $product->is_active
            && $variant->stock_quantity >= $item->quantity;

        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id' => $item->id,
            'quantity' => $item->quantity,
            'unit_price_cents' => $unitPrice,
            'original_unit_price_cents' => $pricing['original_price_cents'],
            'has_promotion' => $pricing['has_promotion'],
            'line_total_cents' => $unitPrice * $item->quantity,
            'is_available' => $isAvailable,
            'max_quantity' => $variant->stock_quantity,
            'variant' => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'stock_quantity' => $variant->stock_quantity,
            ],
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'primary_image' => $primaryImage ? [
                    'url' => $primaryImage->url(),
                    'alt_text' => $primaryImage->alt_text,
                ] : null,
            ],
        ];
    }

    private function calculateSubtotalForCart(Cart $cart): int
    {
        $cart->load([
            'items.variant.product.category',
            'items.variant.product.brand',
        ]);

        return (int) $cart->items
            ->map(fn (CartItem $item) => $this->transformItem($item)['line_total_cents'])
            ->sum();
    }

    private function findPurchasableVariant(int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('is_active', true)
            ->whereHas('product', fn ($query) => $query->visibleInStorefront())
            ->first();

        if ($variant === null) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'Esta variacao nao esta disponivel.',
            ]);
        }

        return $variant;
    }

    private function assertQuantityAvailable(ProductVariant $variant, int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Informe uma quantidade valida.',
            ]);
        }

        if ($quantity > $variant->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Quantidade indisponivel em estoque.',
            ]);
        }
    }
}
