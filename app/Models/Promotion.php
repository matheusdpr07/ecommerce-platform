<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\PromotionScope;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'value',
        'scope',
        'category_id',
        'brand_id',
        'product_id',
        'priority',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'scope' => PromotionScope::class,
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });
    }

    public function appliesToProduct(Product $product): bool
    {
        return match ($this->scope) {
            PromotionScope::AllProducts => true,
            PromotionScope::Category => $this->category_id === $product->category_id,
            PromotionScope::Brand => $this->brand_id !== null && $this->brand_id === $product->brand_id,
            PromotionScope::Product => $this->product_id === $product->id,
        };
    }

    public function applyToPrice(int $priceCents): int
    {
        if ($priceCents <= 0) {
            return 0;
        }

        $discounted = match ($this->type) {
            DiscountType::Percentage => $priceCents - (int) floor($priceCents * $this->value / 100),
            DiscountType::FixedAmount => $priceCents - min($this->value, $priceCents),
        };

        return max($discounted, 0);
    }
}
