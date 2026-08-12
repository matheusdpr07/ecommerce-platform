<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromotionScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromotionRequest;
use App\Http\Requests\Admin\UpdatePromotionRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Promotion::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $promotions = Promotion::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'product:id,name',
            ])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Promotions/Index', [
            'promotions' => $promotions,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Promotion::class);

        return Inertia::render('Admin/Promotions/Create', [
            'scopeOptions' => $this->scopeOptions(),
            'categories' => $this->categoryOptions(),
            'brands' => $this->brandOptions(),
            'products' => $this->productOptions(),
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        Promotion::query()->create($this->normalizeScopeData($request->validated()));

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Promocao criada com sucesso.');
    }

    public function edit(Promotion $promotion): Response
    {
        $this->authorize('update', $promotion);

        return Inertia::render('Admin/Promotions/Edit', [
            'promotion' => $promotion->load([
                'category:id,name',
                'brand:id,name',
                'product:id,name',
            ]),
            'scopeOptions' => $this->scopeOptions(),
            'categories' => $this->categoryOptions(),
            'brands' => $this->brandOptions(),
            'products' => $this->productOptions(),
        ]);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($this->normalizeScopeData($request->validated()));

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Promocao atualizada com sucesso.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->authorize('delete', $promotion);

        $promotion->delete();

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Promocao excluida com sucesso.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function scopeOptions(): array
    {
        return [
            ['value' => PromotionScope::AllProducts->value, 'label' => 'Todos os produtos'],
            ['value' => PromotionScope::Category->value, 'label' => 'Categoria'],
            ['value' => PromotionScope::Brand->value, 'label' => 'Marca'],
            ['value' => PromotionScope::Product->value, 'label' => 'Produto'],
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function brandOptions(): array
    {
        return Brand::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function productOptions(): array
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeScopeData(array $data): array
    {
        $scope = PromotionScope::from($data['scope']);

        $data['category_id'] = $scope === PromotionScope::Category ? $data['category_id'] : null;
        $data['brand_id'] = $scope === PromotionScope::Brand ? $data['brand_id'] : null;
        $data['product_id'] = $scope === PromotionScope::Product ? $data['product_id'] : null;

        return $data;
    }
}
