<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderFulfillmentRequest;
use App\Http\Requests\Admin\UpdateOrderNotesRequest;
use App\Models\Order;
use App\Services\AdminAuditService;
use App\Services\FulfillmentService;
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
        private readonly FulfillmentService $fulfillmentService,
        private readonly AdminAuditService $auditService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('manageAny', Order::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $fulfillmentStatus = $request->string('fulfillment_status')->trim()->toString();
        $dateFrom = $this->normalizeDate($request->string('date_from')->toString());
        $dateTo = $this->normalizeDate($request->string('date_to')->toString());

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $this->orderService->paginateForAdmin(
                $search,
                $status,
                $fulfillmentStatus,
                $dateFrom,
                $dateTo,
            ),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'fulfillment_status' => $fulfillmentStatus,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statuses' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $orderStatus) => [
                    'value' => $orderStatus->value,
                    'label' => $orderStatus->label(),
                ])
                ->all(),
            'fulfillmentStatuses' => collect(FulfillmentStatus::cases())
                ->map(fn (FulfillmentStatus $fulfillmentStatus) => [
                    'value' => $fulfillmentStatus->value,
                    'label' => $fulfillmentStatus->label(),
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
                'internal_notes' => $order->internal_notes,
            ],
            'canRefund' => $order->payment !== null
                && in_array($order->status, [OrderStatus::Paid, OrderStatus::PartiallyRefunded], true),
            'nextFulfillmentStatus' => $this->nextFulfillmentStatus($order),
        ]);
    }

    public function updateFulfillment(
        UpdateOrderFulfillmentRequest $request,
        Order $order,
    ): RedirectResponse {
        $this->authorize('manage', $order);

        $this->fulfillmentService->updateStatus(
            $order,
            $request->user(),
            FulfillmentStatus::from($request->string('fulfillment_status')->toString()),
            $request->string('tracking_code')->trim()->toString() ?: null,
            $request->string('tracking_url')->trim()->toString() ?: null,
        );

        return back()->with('success', 'Etapa logistica do pedido atualizada.');
    }

    public function updateNotes(UpdateOrderNotesRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('manage', $order);

        $this->fulfillmentService->updateInternalNotes(
            $order,
            $request->user(),
            $request->string('internal_notes')->toString() ?: null,
        );

        return back()->with('success', 'Observacao interna atualizada.');
    }

    public function refund(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('refund', $order);

        $payment = $order->payment;

        if ($payment === null) {
            throw ValidationException::withMessages([
                'payment' => 'Este pedido nao possui pagamento para reembolso.',
            ]);
        }

        try {
            $refundedPayment = $this->paymentService->refund($payment);
        } catch (PaymentGatewayException $exception) {
            report($exception);

            return back()->with('error', 'Nao foi possivel concluir o reembolso agora.');
        }

        $this->auditService->record(
            $request->user(),
            'order.refunded',
            $order,
            'Reembolso integral realizado.',
            ['amount_cents' => $refundedPayment->refunded_amount_cents],
        );

        return back()->with('success', 'Reembolso integral realizado com sucesso.');
    }

    /**
     * @return array{value: string, label: string}|null
     */
    private function nextFulfillmentStatus(Order $order): ?array
    {
        if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::PartiallyRefunded], true)) {
            return null;
        }

        $nextStatus = $order->fulfillment_status->next();

        return $nextStatus === null ? null : [
            'value' => $nextStatus->value,
            'label' => $nextStatus->label(),
        ];
    }

    private function normalizeDate(string $date): string
    {
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts)) {
            return '';
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $date : '';
    }
}
