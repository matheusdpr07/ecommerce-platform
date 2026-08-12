<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddressService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data): Address {
            if ($data['is_default'] ?? false) {
                $this->clearDefaultForUser($user);
            }

            if (! $user->addresses()->exists()) {
                $data['is_default'] = true;
            }

            return $user->addresses()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAddress(Address $address, array $data): Address
    {
        return DB::transaction(function () use ($address, $data): Address {
            if ($data['is_default'] ?? false) {
                $this->clearDefaultForUser($address->user);
            }

            $address->update($data);

            if (! $address->user->addresses()->where('is_default', true)->exists()) {
                $address->update(['is_default' => true]);
            }

            return $address->fresh();
        });
    }

    public function deleteAddress(Address $address): void
    {
        DB::transaction(function () use ($address): void {
            $user = $address->user;
            $wasDefault = $address->is_default;

            Cart::query()
                ->where('shipping_address_id', $address->id)
                ->update([
                    'shipping_address_id' => null,
                    'shipping_method_id' => null,
                    'shipping_cents' => null,
                ]);

            $address->delete();

            if ($wasDefault) {
                $nextDefault = $user->addresses()->first();

                if ($nextDefault !== null) {
                    $nextDefault->update(['is_default' => true]);
                }
            }
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(User $user): array
    {
        return $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('label')
            ->get()
            ->map(fn (Address $address) => $this->transformAddress($address))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function transformAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'recipient_phone' => $address->recipient_phone,
            'postal_code' => $address->postal_code,
            'formatted_postal_code' => $address->formattedPostalCode(),
            'street' => $address->street,
            'number' => $address->number,
            'complement' => $address->complement,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'is_default' => $address->is_default,
            'summary' => "{$address->street}, {$address->number} - {$address->city}/{$address->state}",
        ];
    }

    public function assertBelongsToUser(Address $address, User $user): void
    {
        if ($address->user_id !== $user->id) {
            abort(403);
        }
    }

    private function clearDefaultForUser(User $user): void
    {
        $user->addresses()->update(['is_default' => false]);
    }
}
