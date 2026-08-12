<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'number',
        'status',
        'fulfillment_status',
        'tracking_code',
        'tracking_url',
        'internal_notes',
        'subtotal_cents',
        'discount_cents',
        'shipping_cents',
        'total_cents',
        'coupon_id',
        'coupon_code',
        'coupon_name',
        'shipping_method_id',
        'shipping_method_name',
        'recipient_name',
        'recipient_phone',
        'postal_code',
        'street',
        'street_number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'placed_at',
        'preparing_at',
        'shipped_at',
        'delivered_at',
        'fulfillment_cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'placed_at' => 'datetime',
            'preparing_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'fulfillment_cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function formattedPostalCode(): string
    {
        if (strlen($this->postal_code) !== 8) {
            return $this->postal_code;
        }

        return substr($this->postal_code, 0, 5).'-'.substr($this->postal_code, 5);
    }
}
