<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShippingMethodRequest;
use App\Http\Requests\Admin\UpdateShippingMethodRequest;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShippingMethodController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ShippingMethod::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $shippingMethods = ShippingMethod::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/ShippingMethods/Index', [
            'shippingMethods' => $shippingMethods,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ShippingMethod::class);

        return Inertia::render('Admin/ShippingMethods/Create');
    }

    public function store(StoreShippingMethodRequest $request): RedirectResponse
    {
        ShippingMethod::query()->create($request->validated());

        return redirect()
            ->route('admin.shipping-methods.index')
            ->with('success', 'Metodo de frete criado com sucesso.');
    }

    public function edit(ShippingMethod $shippingMethod): Response
    {
        $this->authorize('update', $shippingMethod);

        return Inertia::render('Admin/ShippingMethods/Edit', [
            'shippingMethod' => $shippingMethod,
        ]);
    }

    public function update(UpdateShippingMethodRequest $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->update($request->validated());

        return redirect()
            ->route('admin.shipping-methods.index')
            ->with('success', 'Metodo de frete atualizado com sucesso.');
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $this->authorize('delete', $shippingMethod);

        $shippingMethod->delete();

        return redirect()
            ->route('admin.shipping-methods.index')
            ->with('success', 'Metodo de frete excluido com sucesso.');
    }
}
