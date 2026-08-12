<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'provider_order_id',
        'provider_payment_id',
        'method',
        'status',
        'status_detail',
        'amount_cents',
        'refunded_amount_cents',
        'idempotency_key',
        'refund_idempotency_key',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_ticket_url',
        'expires_at',
        'paid_at',
        'refunded_at',
        'inventory_released_at',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'refunded_amount_cents' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'inventory_released_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
