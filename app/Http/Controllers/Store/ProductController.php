<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\BannerService;
use App\Services\ReviewService;
use App\Services\StorefrontCatalogService;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly StorefrontCatalogService $catalogService,
        private readonly WishlistService $wishlistService,
        private readonly BannerService $bannerService,
        private readonly ReviewService $reviewService,
    ) {}

    public function index(Request $request, ?Category $category = null): Response
    {
        if ($category !== null && ! $category->is_active) {
            abort(404);
        }

        $filters = $this->catalogService->extractFilters($request, $category);

        return Inertia::render('Store/Products/Index', [
            'products' => $this->catalogService->paginateProducts($filters),
            'filters' => $filters,
            'categories' => $this->catalogService->activeCategoryOptions(),
            'brands' => $this->catalogService->activeBrandOptions(),
            'banners' => $this->bannerService->activeForStorefront(),
            'activeCategory' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ] : null,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $productModel = $this->catalogService->findVisibleProduct($slug);

        return Inertia::render('Store/Products/Show', [
            'product' => $this->catalogService->transformDetail($productModel),
            'is_in_wishlist' => $request->user()
                ? $this->wishlistService->isProductInWishlist($request->user(), $productModel->id)
                : false,
            'reviews' => $this->reviewService->forProduct($productModel, $request->user()),
        ]);
    }
}
