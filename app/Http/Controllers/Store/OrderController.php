<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
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
        $this->authorize('viewAny', Order::class);

        return Inertia::render('Store/Orders/Index', [
            'orders' => $this->orderService->listForUser($request->user()),
        ]);
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $orderPayload = $this->orderService->transformOrder($order);

        return Inertia::render('Store/Orders/Show', [
            'order' => [
                ...$orderPayload,
                'payment' => $this->paymentService->transformForStore(
                    $order->payment()->first(),
                ),
            ],
        ]);
    }
}
