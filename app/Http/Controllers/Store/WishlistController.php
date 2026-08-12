<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreWishlistItemRequest;
use App\Models\WishlistItem;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Store/Wishlist/Index', [
            'wishlist' => $this->wishlistService->getPayload($request->user()),
        ]);
    }

    public function store(StoreWishlistItemRequest $request): RedirectResponse
    {
        $this->wishlistService->addItem(
            $request->user(),
            (int) $request->validated('product_id'),
        );

        return back()->with('success', 'Produto adicionado aos favoritos.');
    }

    public function destroy(Request $request, WishlistItem $wishlistItem): RedirectResponse
    {
        abort_unless($wishlistItem->user_id === $request->user()->id, 403);

        $this->wishlistService->removeItem($wishlistItem);

        return back()->with('success', 'Produto removido dos favoritos.');
    }

    public function moveToCart(Request $request, WishlistItem $wishlistItem): RedirectResponse
    {
        abort_unless($wishlistItem->user_id === $request->user()->id, 403);

        $this->wishlistService->moveItemToCart($wishlistItem);

        return redirect()
            ->route('store.cart.index')
            ->with('success', 'Produto movido para o carrinho.');
    }
}
