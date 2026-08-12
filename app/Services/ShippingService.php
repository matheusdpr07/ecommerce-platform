<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ShippingMethod;

class ShippingService
{
    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     price_cents: int,
     *     estimated_days_min: int|null,
     *     estimated_days_max: int|null
     * }>
     */
    public function getAvailableMethods(Cart $cart, int $subtotalAfterDiscountCents): array
    {
        return ShippingMethod::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (ShippingMethod $method) => $method->isEligibleForSubtotal($subtotalAfterDiscountCents))
            ->map(fn (ShippingMethod $method) => $this->transformMethod(
                $method,
                $method->calculatePrice($subtotalAfterDiscountCents),
            ))
            ->values()
            ->all();
    }

    public function resolveMethodPrice(
        ShippingMethod $method,
        int $subtotalAfterDiscountCents,
    ): int {
        if (! $method->is_active) {
            throw new \InvalidArgumentException('Shipping method is inactive.');
        }

        return $method->calculatePrice($subtotalAfterDiscountCents);
    }

    public function findEligibleMethod(int $methodId, int $subtotalAfterDiscountCents): ShippingMethod
    {
        $method = ShippingMethod::query()->active()->findOrFail($methodId);

        if (! $method->isEligibleForSubtotal($subtotalAfterDiscountCents)) {
            throw new \InvalidArgumentException('Shipping method is not available for this order.');
        }

        return $method;
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     description: string|null,
     *     price_cents: int,
     *     estimated_days_min: int|null,
     *     estimated_days_max: int|null
     * }
     */
    public function transformMethod(ShippingMethod $method, int $priceCents): array
    {
        return [
            'id' => $method->id,
            'name' => $method->name,
            'description' => $method->description,
            'price_cents' => $priceCents,
            'estimated_days_min' => $method->estimated_days_min,
            'estimated_days_max' => $method->estimated_days_max,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function transformSelectedMethod(?ShippingMethod $method, ?int $priceCents): ?array
    {
        if ($method === null || $priceCents === null) {
            return null;
        }

        return $this->transformMethod($method, $priceCents);
    }
}
