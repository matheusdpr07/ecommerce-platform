<?php

namespace App\Http\Controllers\Store;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function store(Order $order): RedirectResponse
    {
        $this->authorize('pay', $order);

        try {
            $this->paymentService->createPixForOrder($order);
        } catch (PaymentGatewayException $exception) {
            report($exception);

            return back()->with('error', 'Nao foi possivel gerar o Pix agora. Tente novamente.');
        }

        return back()->with('success', 'Pix gerado com sucesso.');
    }
}
