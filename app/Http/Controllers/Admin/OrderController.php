<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('manageAny', Order::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $this->orderService->paginateForAdmin($search, $status),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statuses' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $orderStatus) => [
                    'value' => $orderStatus->value,
                    'label' => $orderStatus->label(),
                ])
                ->all(),
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('manage', $order);

        $order->load(['user:id,name,email', 'items', 'payment']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                ...$this->orderService->transformOrder($order),
                'customer' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'payment' => $this->paymentService->transformForStore($order->payment),
            ],
            'canRefund' => $order->payment !== null
                && in_array($order->status, [OrderStatus::Paid, OrderStatus::PartiallyRefunded], true),
        ]);
    }

    public function refund(Order $order): RedirectResponse
    {
        $this->authorize('refund', $order);

        $payment = $order->payment;

        if ($payment === null) {
            throw ValidationException::withMessages([
                'payment' => 'Este pedido nao possui pagamento para reembolso.',
            ]);
        }

        try {
            $this->paymentService->refund($payment);
        } catch (PaymentGatewayException $exception) {
            report($exception);

            return back()->with('error', 'Nao foi possivel concluir o reembolso agora.');
        }

        return back()->with('success', 'Reembolso integral realizado com sucesso.');
    }
}
