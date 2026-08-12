<?php

namespace App\Http\Controllers\Store;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\UpdateCheckoutRequest;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        try {
            $checkout = $this->checkoutService->buildCheckoutData($request);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('store.cart.index')
                ->withErrors($exception->errors());
        }

        return Inertia::render('Store/Checkout/Index', $checkout);
    }

    public function update(UpdateCheckoutRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->checkoutService->updateCheckout(
                $request,
                (int) $data['shipping_address_id'],
                isset($data['shipping_method_id']) ? (int) $data['shipping_method_id'] : null,
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Checkout atualizado.');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $order = $this->orderService->placeOrder($request);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        if ($this->paymentService->isConfigured()) {
            try {
                $this->paymentService->createPixForOrder($order);
            } catch (PaymentGatewayException $exception) {
                report($exception);

                return redirect()
                    ->route('store.orders.show', $order)
                    ->with('success', 'Pedido realizado com sucesso.')
                    ->with('error', 'O Pix nao foi gerado. Tente novamente na pagina do pedido.');
            }
        }

        return redirect()
            ->route('store.orders.show', $order)
            ->with('success', 'Pedido realizado com sucesso.');
    }
}
