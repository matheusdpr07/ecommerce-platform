<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'recipient_phone',
        'postal_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Address $address): void {
            $address->postal_code = preg_replace('/\D/', '', $address->postal_code) ?? '';
            $address->state = strtoupper($address->state);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public function formatted(): array
    {
        return [
            'line1' => "{$this->street}, {$this->number}",
            'line2' => trim("{$this->neighborhood} - {$this->city}/{$this->state}"),
            'postal_code' => $this->formattedPostalCode(),
        ];
    }

    public function formattedPostalCode(): string
    {
        if (strlen($this->postal_code) !== 8) {
            return $this->postal_code;
        }

        return substr($this->postal_code, 0, 5).'-'.substr($this->postal_code, 5);
    }
}
