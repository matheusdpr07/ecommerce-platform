<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly ShippingService $shippingService,
        private readonly AddressService $addressService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildCheckoutData(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();

        $cart = $this->resolveUserCart($request);
        $cartPayload = $this->cartService->getCartPayload($request);
        $this->assertCartReadyForCheckout($cartPayload);

        $subtotalAfterDiscount = max(
            $cartPayload['subtotal_cents'] - $cartPayload['discount_cents'],
            0,
        );

        $addresses = $this->addressService->listForUser($user);
        $shippingMethods = $this->shippingService->getAvailableMethods($cart, $subtotalAfterDiscount);
        $selectedAddress = $this->resolveSelectedAddress($cart, $user);
        $selectedShipping = $this->resolveSelectedShipping($cart, $subtotalAfterDiscount, $shippingMethods);

        $shippingCents = $selectedShipping['price_cents'] ?? 0;
        $grandTotal = $subtotalAfterDiscount + $shippingCents;

        return [
            'cart' => $cartPayload,
            'addresses' => $addresses,
            'shipping_methods' => $shippingMethods,
            'selected_address_id' => $selectedAddress?->id,
            'selected_shipping_method_id' => $selectedShipping['id'] ?? null,
            'shipping' => $selectedShipping,
            'shipping_cents' => $shippingCents,
            'grand_total_cents' => $grandTotal,
            'is_ready' => $selectedAddress !== null && $selectedShipping !== null,
        ];
    }

    public function updateCheckout(Request $request, ?int $addressId, ?int $shippingMethodId): void
    {
        /** @var User $user */
        $user = $request->user();

        $cart = $this->resolveUserCart($request);
        $cartPayload = $this->cartService->getCartPayload($request);
        $this->assertCartReadyForCheckout($cartPayload);

        $subtotalAfterDiscount = max(
            $cartPayload['subtotal_cents'] - $cartPayload['discount_cents'],
            0,
        );

        $address = Address::query()
            ->whereKey($addressId)
            ->where('user_id', $user->id)
            ->first();

        if ($address === null) {
            throw ValidationException::withMessages([
                'shipping_address_id' => 'Selecione um endereco valido.',
            ]);
        }

        $updates = [
            'shipping_address_id' => $address->id,
        ];

        if ($shippingMethodId !== null) {
            $method = $this->shippingService->findEligibleMethod(
                $shippingMethodId,
                $subtotalAfterDiscount,
            );

            $updates['shipping_method_id'] = $method->id;
            $updates['shipping_cents'] = $this->shippingService->resolveMethodPrice(
                $method,
                $subtotalAfterDiscount,
            );
        } else {
            $updates['shipping_method_id'] = null;
            $updates['shipping_cents'] = null;
        }

        $cart->update($updates);
    }

    public function resolveUserCart(Request $request): Cart
    {
        /** @var User $user */
        $user = $request->user();

        if ($user === null) {
            throw ValidationException::withMessages([
                'cart' => 'Faca login para continuar o checkout.',
            ]);
        }

        return Cart::query()->firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * @param  array<string, mixed>  $cartPayload
     */
    private function assertCartReadyForCheckout(array $cartPayload): void
    {
        if ($cartPayload['item_count'] === 0) {
            throw ValidationException::withMessages([
                'cart' => 'Seu carrinho esta vazio.',
            ]);
        }

        $hasUnavailableItems = collect($cartPayload['items'])
            ->contains(fn (array $item) => $item['is_available'] === false);

        if ($hasUnavailableItems) {
            throw ValidationException::withMessages([
                'cart' => 'Revise os itens indisponiveis no carrinho antes de continuar.',
            ]);
        }
    }

    private function resolveSelectedAddress(Cart $cart, User $user): ?Address
    {
        if ($cart->shipping_address_id === null) {
            return $user->addresses()->where('is_default', true)->first();
        }

        return Address::query()
            ->whereKey($cart->shipping_address_id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * @param  list<array<string, mixed>>  $availableMethods
     * @return array<string, mixed>|null
     */
    private function resolveSelectedShipping(
        Cart $cart,
        int $subtotalAfterDiscount,
        array $availableMethods,
    ): ?array {
        if ($cart->shipping_method_id === null) {
            return null;
        }

        $available = collect($availableMethods)->firstWhere('id', $cart->shipping_method_id);

        if ($available !== null) {
            return $available;
        }

        $cart->update([
            'shipping_method_id' => null,
            'shipping_cents' => null,
        ]);

        return null;
    }
}
