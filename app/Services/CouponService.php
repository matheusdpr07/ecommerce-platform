<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function findValidCouponByCode(string $code): ?Coupon
    {
        return Coupon::query()
            ->active()
            ->where('code', strtoupper(trim($code)))
            ->first();
    }

    public function applyToCart(Cart $cart, string $code, int $subtotalCents): Coupon
    {
        $coupon = $this->findValidCouponByCode($code);

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'code' => 'Cupom invalido ou indisponivel.',
            ]);
        }

        $this->assertCouponUsable($coupon, $subtotalCents);

        $cart->update(['coupon_id' => $coupon->id]);

        return $coupon;
    }

    public function removeFromCart(Cart $cart): void
    {
        $cart->update(['coupon_id' => null]);
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     discount_cents: int
     * }|null
     */
    public function resolveCartCoupon(Cart $cart, int $subtotalCents): ?array
    {
        $cart->loadMissing('coupon');

        if ($cart->coupon === null) {
            return null;
        }

        try {
            $this->assertCouponUsable($cart->coupon, $subtotalCents);
        } catch (ValidationException) {
            $cart->update(['coupon_id' => null]);

            return null;
        }

        return [
            'code' => $cart->coupon->code,
            'name' => $cart->coupon->name,
            'discount_cents' => $cart->coupon->calculateDiscount($subtotalCents),
        ];
    }

    public function assertCouponUsableForOrder(Coupon $coupon, int $subtotalCents): void
    {
        $this->assertCouponUsable($coupon, $subtotalCents);
    }

    public function recordUsage(Coupon $coupon): void
    {
        $coupon->refresh();

        if (! $coupon->hasRemainingUses()) {
            throw ValidationException::withMessages([
                'code' => 'Este cupom atingiu o limite de uso.',
            ]);
        }

        $coupon->increment('usage_count');
    }

    private function assertCouponUsable(Coupon $coupon, int $subtotalCents): void
    {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'code' => 'Cupom invalido ou indisponivel.',
            ]);
        }

        if (! $coupon->hasRemainingUses()) {
            throw ValidationException::withMessages([
                'code' => 'Este cupom atingiu o limite de uso.',
            ]);
        }

        if ($coupon->min_order_cents !== null && $subtotalCents < $coupon->min_order_cents) {
            throw ValidationException::withMessages([
                'code' => 'Pedido minimo nao atingido para este cupom.',
            ]);
        }

        if ($subtotalCents <= 0) {
            throw ValidationException::withMessages([
                'code' => 'Adicione itens ao carrinho antes de aplicar o cupom.',
            ]);
        }
    }
}
