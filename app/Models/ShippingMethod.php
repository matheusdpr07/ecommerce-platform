<?php

namespace App\Models;

use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price_cents',
        'free_above_cents',
        'min_order_cents',
        'max_order_cents',
        'estimated_days_min',
        'estimated_days_max',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isEligibleForSubtotal(int $subtotalCents): bool
    {
        if ($this->min_order_cents !== null && $subtotalCents < $this->min_order_cents) {
            return false;
        }

        if ($this->max_order_cents !== null && $subtotalCents > $this->max_order_cents) {
            return false;
        }

        return true;
    }

    public function calculatePrice(int $subtotalCents): int
    {
        if (! $this->isEligibleForSubtotal($subtotalCents)) {
            throw new \InvalidArgumentException('Shipping method is not eligible for this subtotal.');
        }

        if ($this->free_above_cents !== null && $subtotalCents >= $this->free_above_cents) {
            return 0;
        }

        return $this->price_cents;
    }
}
