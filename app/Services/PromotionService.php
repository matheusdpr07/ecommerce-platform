<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Support\Collection;

class PromotionService
{
    /** @var Collection<int, Promotion>|null */
    private ?Collection $activePromotions = null;

    /**
     * @return array{
     *     price_cents: int,
     *     original_price_cents: int,
     *     has_promotion: bool,
     *     promotion: array{id: int, name: string}|null
     * }
     */
    public function resolveVariantPricing(ProductVariant $variant, Product $product): array
    {
        $originalPrice = $variant->price_cents;
        $bestPromotion = null;
        $bestPrice = $originalPrice;

        foreach ($this->getActivePromotions() as $promotion) {
            if (! $promotion->appliesToProduct($product)) {
                continue;
            }

            $promotionalPrice = $promotion->applyToPrice($originalPrice);

            if ($promotionalPrice < $bestPrice) {
                $bestPrice = $promotionalPrice;
                $bestPromotion = $promotion;
            }
        }

        if ($bestPromotion === null) {
            return [
                'price_cents' => $originalPrice,
                'original_price_cents' => $originalPrice,
                'has_promotion' => false,
                'promotion' => null,
            ];
        }

        return [
            'price_cents' => $bestPrice,
            'original_price_cents' => $originalPrice,
            'has_promotion' => true,
            'promotion' => [
                'id' => $bestPromotion->id,
                'name' => $bestPromotion->name,
            ],
        ];
    }

    /**
     * @return Collection<int, Promotion>
     */
    private function getActivePromotions(): Collection
    {
        if ($this->activePromotions === null) {
            $this->activePromotions = Promotion::query()
                ->active()
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get();
        }

        return $this->activePromotions;
    }
}
