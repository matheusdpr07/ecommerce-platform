<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\AdminAuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getData(int $period): array
    {
        $start = now()->subDays($period - 1)->startOfDay();
        $end = now()->endOfDay();
        $revenuePayments = Payment::query()
            ->whereBetween('paid_at', [$start, $end])
            ->whereIn('status', $this->revenuePaymentStatuses());
        $paidOrders = (clone $revenuePayments)->count();
        $netRevenueCents = (int) (clone $revenuePayments)
            ->selectRaw('COALESCE(SUM(amount_cents - refunded_amount_cents), 0) as aggregate')
            ->value('aggregate');
        $availableVariants = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', fn (Builder $query) => $query->where('is_active', true));

        return [
            'period' => $period,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'metrics' => [
                'net_revenue_cents' => $netRevenueCents,
                'paid_orders' => $paidOrders,
                'average_ticket_cents' => $paidOrders > 0
                    ? intdiv($netRevenueCents, $paidOrders)
                    : 0,
                'pending_payments' => Payment::query()
                    ->whereBetween('created_at', [$start, $end])
                    ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing])
                    ->count(),
                'refunds_count' => Payment::query()
                    ->whereBetween('refunded_at', [$start, $end])
                    ->where('refunded_amount_cents', '>', 0)
                    ->count(),
                'refunds_amount_cents' => (int) Payment::query()
                    ->whereBetween('refunded_at', [$start, $end])
                    ->sum('refunded_amount_cents'),
                'new_customers' => User::query()
                    ->customers()
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ],
            'operations' => [
                'awaiting_fulfillment' => Order::query()
                    ->whereIn('status', [OrderStatus::Paid, OrderStatus::PartiallyRefunded])
                    ->where('fulfillment_status', FulfillmentStatus::Pending)
                    ->count(),
                'preparing' => Order::query()
                    ->whereIn('status', [OrderStatus::Paid, OrderStatus::PartiallyRefunded])
                    ->where('fulfillment_status', FulfillmentStatus::Preparing)
                    ->count(),
                'low_stock' => (clone $availableVariants)
                    ->where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->count(),
                'out_of_stock' => (clone $availableVariants)
                    ->where('stock_quantity', 0)
                    ->count(),
            ],
            'daily_trend' => $this->dailyTrend($start, $end),
            'recent_orders' => Order::query()
                ->with(['user:id,name,email', 'payment'])
                ->withCount('items')
                ->latest('placed_at')
                ->limit(8)
                ->get()
                ->map(fn (Order $order): array => [
                    ...$this->orderService->transformOrderSummary($order),
                    'customer' => [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'email' => $order->user->email,
                    ],
                ])
                ->all(),
            'recent_activity' => AdminAuditLog::query()
                ->with('user:id,name')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (AdminAuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user_name' => $log->user?->name ?? 'Administrador removido',
                    'created_at' => $log->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /**
     * @return list<array{date: string, revenue_cents: int, orders: int}>
     */
    private function dailyTrend(Carbon $start, Carbon $end): array
    {
        $rows = Payment::query()
            ->selectRaw('DATE(paid_at) as payment_date')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(amount_cents - refunded_amount_cents), 0) as revenue_cents')
            ->whereBetween('paid_at', [$start, $end])
            ->whereIn('status', $this->revenuePaymentStatuses())
            ->groupByRaw('DATE(paid_at)')
            ->get()
            ->keyBy('payment_date');

        $trend = [];
        $date = $start->copy();

        while ($date->lte($end)) {
            $dateKey = $date->toDateString();
            $row = $rows->get($dateKey);
            $trend[] = [
                'date' => $dateKey,
                'revenue_cents' => (int) ($row?->revenue_cents ?? 0),
                'orders' => (int) ($row?->orders_count ?? 0),
            ];
            $date->addDay();
        }

        return $trend;
    }

    /**
     * @return list<string>
     */
    private function revenuePaymentStatuses(): array
    {
        return [
            PaymentStatus::Approved->value,
            PaymentStatus::PartiallyRefunded->value,
            PaymentStatus::Refunded->value,
        ];
    }
}
