<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreAddressRequest;
use App\Http\Requests\Store\UpdateAddressRequest;
use App\Models\Address;
use App\Services\AddressService;
use App\Support\BrazilianStates;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function __construct(
        private readonly AddressService $addressService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Address::class);

        return Inertia::render('Store/Addresses/Index', [
            'addresses' => $this->addressService->listForUser(request()->user()),
            'states' => BrazilianStates::codes(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Address::class);

        return Inertia::render('Store/Addresses/Create', [
            'states' => BrazilianStates::codes(),
        ]);
    }

    public function store(StoreAddressRequest $request): RedirectResponse
    {
        $this->addressService->createForUser(
            $request->user(),
            $request->validated(),
        );

        return redirect()
            ->route('store.addresses.index')
            ->with('success', 'Endereco cadastrado com sucesso.');
    }

    public function edit(Address $address): Response
    {
        $this->authorize('update', $address);

        return Inertia::render('Store/Addresses/Edit', [
            'address' => $this->addressService->transformAddress($address),
            'states' => BrazilianStates::codes(),
        ]);
    }

    public function update(UpdateAddressRequest $request, Address $address): RedirectResponse
    {
        $this->addressService->updateAddress($address, $request->validated());

        return redirect()
            ->route('store.addresses.index')
            ->with('success', 'Endereco atualizado com sucesso.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);

        $this->addressService->deleteAddress($address);

        return redirect()
            ->route('store.addresses.index')
            ->with('success', 'Endereco removido com sucesso.');
    }
}
