<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreReviewRequest;
use App\Http\Requests\Store\UpdateReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    public function store(StoreReviewRequest $request, Product $product): RedirectResponse
    {
        abort_unless(
            Product::query()->visibleInStorefront()->whereKey($product->getKey())->exists(),
            404,
        );

        $this->reviewService->create(
            $request->user(),
            $product,
            $request->validated(),
        );

        return redirect()
            ->route('store.products.show', $product->slug)
            ->withFragment('avaliacoes')
            ->with('success', 'Avaliação enviada para moderação. Obrigado por compartilhar sua experiência.');
    }

    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->reviewService->update($review, $request->validated());
        $review->loadMissing('product:id,slug');

        return redirect()
            ->route('store.products.show', $review->product->slug)
            ->withFragment('avaliacoes')
            ->with('success', 'Avaliação atualizada e enviada novamente para moderação.');
    }
}
