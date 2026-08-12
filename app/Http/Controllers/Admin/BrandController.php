<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Brand::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $brands = Brand::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Brands/Index', [
            'brands' => $brands,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Brand::class);

        return Inertia::render('Admin/Brands/Create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Brand::generateUniqueSlug($data['name']);

        Brand::query()->create($data);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Marca criada com sucesso.');
    }

    public function edit(Brand $brand): Response
    {
        $this->authorize('update', $brand);

        return Inertia::render('Admin/Brands/Edit', [
            'brand' => $brand,
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Brand::generateUniqueSlug($data['name'], $brand->id);

        $brand->update($data);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Marca atualizada com sucesso.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        $brand->delete();

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Marca excluida com sucesso.');
    }
}
