<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();
        $verification = $request->string('verification')->trim()->toString();
        $activity = $request->string('activity')->trim()->toString();

        if (! in_array($verification, ['', 'verified', 'unverified'], true)) {
            $verification = '';
        }

        if (! in_array($activity, ['', 'with_orders', 'without_orders'], true)) {
            $activity = '';
        }

        $netSpentQuery = Payment::query()
            ->selectRaw('COALESCE(SUM(payments.amount_cents - payments.refunded_amount_cents), 0)')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->whereColumn('orders.user_id', 'users.id')
            ->whereIn('payments.status', $this->revenuePaymentStatuses());

        $customers = User::query()
            ->customers()
            ->withCount(['orders', 'addresses'])
            ->withMax('orders', 'placed_at')
            ->addSelect(['net_spent_cents' => $netSpentQuery])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($verification === 'verified', fn (Builder $query) => $query->whereNotNull('email_verified_at'))
            ->when($verification === 'unverified', fn (Builder $query) => $query->whereNull('email_verified_at'))
            ->when($activity === 'with_orders', fn (Builder $query) => $query->has('orders'))
            ->when($activity === 'without_orders', fn (Builder $query) => $query->doesntHave('orders'))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'email_verified_at' => $customer->email_verified_at?->toIso8601String(),
                'orders_count' => $customer->orders_count,
                'addresses_count' => $customer->addresses_count,
                'net_spent_cents' => (int) $customer->net_spent_cents,
                'last_order_at' => $customer->orders_max_placed_at,
                'created_at' => $customer->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'verification' => $verification,
                'activity' => $activity,
            ],
        ]);
    }

    public function show(User $customer): Response
    {
        $this->authorize('view', $customer);
        abort_unless($customer->isCustomer(), 404);

        $customer->load(['addresses' => fn ($query) => $query
            ->orderByDesc('is_default')
            ->latest()]);

        $orders = $customer->orders()
            ->with(['payment'])
            ->withCount('items')
            ->latest('placed_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($order): array => [
                ...$this->orderService->transformOrderSummary($order),
                'payment' => $order->payment === null ? null : [
                    'status' => $order->payment->status->value,
                    'status_label' => $order->payment->status->label(),
                ],
            ]);

        $revenuePayments = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.user_id', $customer->id)
            ->whereIn('payments.status', $this->revenuePaymentStatuses());
        $paidOrdersCount = (clone $revenuePayments)->count();
        $netSpentCents = (int) (clone $revenuePayments)
            ->selectRaw('COALESCE(SUM(payments.amount_cents - payments.refunded_amount_cents), 0) as aggregate')
            ->value('aggregate');

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'email_verified_at' => $customer->email_verified_at?->toIso8601String(),
                'created_at' => $customer->created_at?->toIso8601String(),
                'addresses' => $customer->addresses->map(fn ($address): array => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'recipient_name' => $address->recipient_name,
                    'recipient_phone' => $address->recipient_phone,
                    'postal_code' => $address->formattedPostalCode(),
                    'line1' => $address->formatted()['line1'],
                    'line2' => $address->formatted()['line2'],
                    'complement' => $address->complement,
                    'is_default' => $address->is_default,
                ])->values()->all(),
            ],
            'orders' => $orders,
            'summary' => [
                'orders_count' => $customer->orders()->count(),
                'paid_orders_count' => $paidOrdersCount,
                'net_spent_cents' => $netSpentCents,
                'average_ticket_cents' => $paidOrdersCount > 0
                    ? intdiv($netSpentCents, $paidOrdersCount)
                    : 0,
            ],
        ]);
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
