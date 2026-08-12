<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
        private readonly ShippingService $shippingService,
        private readonly PromotionService $promotionService,
    ) {}

    public function placeOrder(Request $request): Order
    {
        /** @var User $user */
        $user = $request->user();

        return DB::transaction(function () use ($request, $user): Order {
            $cart = $this->checkoutService->resolveUserCart($request);
            $cart->load([
                'coupon',
                'shippingAddress',
                'shippingMethod',
                'items.variant.product.category',
                'items.variant.product.brand',
            ]);

            $cartPayload = $this->cartService->getCartPayload($request);
            $this->assertCartReadyForOrder($cartPayload);
            $this->assertCheckoutSelectionsReady($cart, $user, $cartPayload);

            $subtotalCents = $cartPayload['subtotal_cents'];
            $discountCents = $cartPayload['discount_cents'];
            $subtotalAfterDiscount = max($subtotalCents - $discountCents, 0);

            $address = $cart->shippingAddress;
            $method = $this->shippingService->findEligibleMethod(
                (int) $cart->shipping_method_id,
                $subtotalAfterDiscount,
            );
            $shippingCents = $this->shippingService->resolveMethodPrice(
                $method,
                $subtotalAfterDiscount,
            );

            if ($cart->shipping_cents !== $shippingCents) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => 'O valor do frete foi atualizado. Revise o checkout.',
                ]);
            }

            if ($cart->coupon !== null) {
                $this->couponService->assertCouponUsableForOrder($cart->coupon, $subtotalCents);
            }

            $lockedVariants = $this->lockAndValidateStock($cart);

            $order = Order::query()->create([
                'user_id' => $user->id,
                'number' => $this->generateTemporaryNumber(),
                'status' => OrderStatus::PendingPayment,
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'shipping_cents' => $shippingCents,
                'total_cents' => $subtotalAfterDiscount + $shippingCents,
                'coupon_id' => $cart->coupon_id,
                'coupon_code' => $cart->coupon?->code,
                'coupon_name' => $cart->coupon?->name,
                'shipping_method_id' => $method->id,
                'shipping_method_name' => $method->name,
                'recipient_name' => $address->recipient_name,
                'recipient_phone' => $address->recipient_phone,
                'postal_code' => $address->postal_code,
                'street' => $address->street,
                'street_number' => $address->number,
                'complement' => $address->complement,
                'neighborhood' => $address->neighborhood,
                'city' => $address->city,
                'state' => $address->state,
                'placed_at' => now(),
            ]);

            $order->update([
                'number' => sprintf('PED-%08d', $order->id),
            ]);

            foreach ($cart->items as $item) {
                $variant = $lockedVariants[$item->product_variant_id];
                $product = $variant->product;
                $pricing = $this->promotionService->resolveVariantPricing($variant, $product);
                $unitPrice = $pricing['price_cents'];
                $lineTotal = $unitPrice * $item->quantity;

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'variant_name' => $variant->name,
                    'variant_sku' => $variant->sku,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $unitPrice,
                    'original_unit_price_cents' => $pricing['has_promotion']
                        ? $pricing['original_price_cents']
                        : null,
                    'line_total_cents' => $lineTotal,
                ]);

                $newStock = $variant->stock_quantity - $item->quantity;
                $variant->update(['stock_quantity' => $newStock]);

                $variant->stockMovements()->create([
                    'user_id' => $user->id,
                    'quantity_change' => -$item->quantity,
                    'quantity_after' => $newStock,
                    'reason' => StockMovementReason::Sale,
                    'notes' => "Pedido {$order->number}",
                ]);
            }

            if ($cart->coupon !== null) {
                $this->couponService->recordUsage($cart->coupon);
            }

            $this->cartService->clearCart($cart);

            return $order->fresh(['items']);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(User $user): array
    {
        return $user->orders()
            ->withCount('items')
            ->latest('placed_at')
            ->get()
            ->map(fn (Order $order) => $this->transformOrderSummary($order))
            ->all();
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForAdmin(string $search, string $status): LengthAwarePaginator
    {
        $statusEnum = OrderStatus::tryFrom($status);

        return Order::query()
            ->with(['user:id,name,email', 'payment'])
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($statusEnum !== null, fn ($query) => $query->where('status', $statusEnum))
            ->latest('placed_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order) => [
                ...$this->transformOrderSummary($order),
                'customer' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'payment' => $order->payment ? [
                    'status' => $order->payment->status->value,
                    'status_label' => $order->payment->status->label(),
                    'method' => $order->payment->method,
                ] : null,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function transformOrderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'item_count' => (int) ($order->items_count ?? $order->items()->count()),
            'total_cents' => $order->total_cents,
            'placed_at' => $order->placed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function transformOrder(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'subtotal_cents' => $order->subtotal_cents,
            'discount_cents' => $order->discount_cents,
            'shipping_cents' => $order->shipping_cents,
            'total_cents' => $order->total_cents,
            'coupon' => $order->coupon_code ? [
                'code' => $order->coupon_code,
                'name' => $order->coupon_name,
            ] : null,
            'shipping_method_name' => $order->shipping_method_name,
            'shipping_address' => [
                'recipient_name' => $order->recipient_name,
                'recipient_phone' => $order->recipient_phone,
                'postal_code' => $order->formattedPostalCode(),
                'street' => $order->street,
                'number' => $order->street_number,
                'complement' => $order->complement,
                'neighborhood' => $order->neighborhood,
                'city' => $order->city,
                'state' => $order->state,
                'summary' => "{$order->street}, {$order->street_number} - {$order->city}/{$order->state}",
            ],
            'placed_at' => $order->placed_at?->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'product_slug' => $item->product_slug,
                'variant_name' => $item->variant_name,
                'variant_sku' => $item->variant_sku,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'original_unit_price_cents' => $item->original_unit_price_cents,
                'line_total_cents' => $item->line_total_cents,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $cartPayload
     */
    private function assertCartReadyForOrder(array $cartPayload): void
    {
        if ($cartPayload['item_count'] === 0) {
            throw ValidationException::withMessages([
                'cart' => 'Seu carrinho esta vazio.',
            ]);
        }

        $hasUnavailableItems = collect($cartPayload['items'])
            ->contains(fn (array $item) => $item['is_available'] === false);

        if ($hasUnavailableItems) {
            throw ValidationException::withMessages([
                'cart' => 'Revise os itens indisponiveis no carrinho antes de continuar.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $cartPayload
     */
    private function assertCheckoutSelectionsReady(Cart $cart, User $user, array $cartPayload): void
    {
        if ($cart->shipping_address_id === null || $cart->shipping_method_id === null || $cart->shipping_cents === null) {
            throw ValidationException::withMessages([
                'checkout' => 'Selecione endereco e frete antes de confirmar o pedido.',
            ]);
        }

        $address = $cart->shippingAddress;

        if ($address === null || $address->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'shipping_address_id' => 'Selecione um endereco valido.',
            ]);
        }

        $subtotalAfterDiscount = max(
            $cartPayload['subtotal_cents'] - $cartPayload['discount_cents'],
            0,
        );

        try {
            $this->shippingService->findEligibleMethod(
                (int) $cart->shipping_method_id,
                $subtotalAfterDiscount,
            );
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([
                'shipping_method_id' => 'Selecione uma opcao de frete valida.',
            ]);
        }
    }

    /**
     * @return array<int, ProductVariant>
     */
    private function lockAndValidateStock(Cart $cart): array
    {
        $variantIds = $cart->items->pluck('product_variant_id')->all();

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->lockForUpdate()
            ->with('product')
            ->get()
            ->keyBy('id');

        foreach ($cart->items as $item) {
            $variant = $variants->get($item->product_variant_id);

            if ($variant === null
                || ! $variant->is_active
                || ! $variant->product->is_active
                || $variant->stock_quantity < $item->quantity
            ) {
                throw ValidationException::withMessages([
                    'cart' => 'Um ou mais itens ficaram indisponiveis. Revise o carrinho.',
                ]);
            }
        }

        return $variants->all();
    }

    private function generateTemporaryNumber(): string
    {
        return 'TMP-'.now()->format('YmdHisu');
    }
}
