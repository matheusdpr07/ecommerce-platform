<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\ApplyCartCouponRequest;
use App\Http\Requests\Store\StoreCartItemRequest;
use App\Http\Requests\Store\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Store/Cart/Index', [
            'cart' => $this->cartService->getCartPayload($request),
        ]);
    }

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->cartService->addItem(
            $request,
            (int) $data['product_variant_id'],
            (int) $data['quantity'],
        );

        return back()->with('success', 'Produto adicionado ao carrinho.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItem($request, $cartItem);

        $this->cartService->updateItemQuantity(
            $cartItem,
            (int) $request->validated('quantity'),
        );

        return back()->with('success', 'Carrinho atualizado.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCartItem($request, $cartItem);

        $this->cartService->removeItem($cartItem);

        return back()->with('success', 'Item removido do carrinho.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $cart = $this->cartService->resolveCart($request);
        $this->cartService->clearCart($cart);

        return back()->with('success', 'Carrinho esvaziado.');
    }

    public function applyCoupon(ApplyCartCouponRequest $request): RedirectResponse
    {
        $this->cartService->applyCoupon(
            $request,
            $request->validated('code'),
        );

        return back()->with('success', 'Cupom aplicado com sucesso.');
    }

    public function removeCoupon(Request $request): RedirectResponse
    {
        $this->cartService->removeCoupon($request);

        return back()->with('success', 'Cupom removido.');
    }

    private function authorizeCartItem(Request $request, CartItem $cartItem): void
    {
        abort_unless(
            $this->cartService->cartBelongsToRequest($cartItem->cart, $request),
            403,
        );
    }
}
