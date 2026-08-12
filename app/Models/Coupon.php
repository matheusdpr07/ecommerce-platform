<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_order_cents',
        'max_discount_cents',
        'usage_limit',
        'usage_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon): void {
            $coupon->code = strtoupper(trim($coupon->code));
        });
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
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

    public function hasRemainingUses(): bool
    {
        if ($this->usage_limit === null) {
            return true;
        }

        return $this->usage_count < $this->usage_limit;
    }

    public function calculateDiscount(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $discount = match ($this->type) {
            DiscountType::Percentage => (int) floor($subtotalCents * $this->value / 100),
            DiscountType::FixedAmount => min($this->value, $subtotalCents),
        };

        if ($this->type === DiscountType::Percentage && $this->max_discount_cents !== null) {
            $discount = min($discount, $this->max_discount_cents);
        }

        return min($discount, $subtotalCents);
    }
}
