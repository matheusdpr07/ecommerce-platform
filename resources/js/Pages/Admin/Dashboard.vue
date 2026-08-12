<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { AdminOrderSummary } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface DailyTrend {
    date: string;
    revenue_cents: number;
    orders: number;
}

const props = defineProps<{
    period: number;
    period_start: string;
    period_end: string;
    metrics: {
        net_revenue_cents: number;
        paid_orders: number;
        average_ticket_cents: number;
        pending_payments: number;
        refunds_count: number;
        refunds_amount_cents: number;
        new_customers: number;
    };
    operations: {
        awaiting_fulfillment: number;
        preparing: number;
        low_stock: number;
        out_of_stock: number;
    };
    daily_trend: DailyTrend[];
    recent_orders: AdminOrderSummary[];
    recent_activity: Array<{
        id: number;
        action: string;
        description: string;
        user_name: string;
        created_at?: string | null;
    }>;
}>();

const selectedPeriod = ref(props.period);
const maxRevenue = computed(() =>
    Math.max(...props.daily_trend.map((day) => day.revenue_cents), 1),
);

const changePeriod = () => {
    router.get(
        route('admin.dashboard'),
        { period: selectedPeriod.value },
        { preserveState: true, replace: true },
    );
};

const barHeight = (value: number) =>
    `${Math.max((value / maxRevenue.value) * 100, value > 0 ? 5 : 1)}%`;

const formatDate = (value?: string | null, withTime = false) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              ...(withTime ? { timeStyle: 'short' as const } : {}),
          }).format(new Date(value))
        : '';
</script>

<template>
    <Head title="Painel administrativo" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">Visão geral</h2>
                    <p class="mt-1 text-sm text-gray-500">Indicadores de {{ formatDate(period_start) }} a {{ formatDate(period_end) }}</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    Período
                    <select v-model.number="selectedPeriod" class="rounded-md border-gray-300 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @change="changePeriod">
                        <option :value="7">7 dias</option>
                        <option :value="30">30 dias</option>
                        <option :value="90">90 dias</option>
                    </select>
                </label>
            </div>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">Receita líquida</p>
                <p class="mt-2 text-3xl font-semibold text-green-700">{{ formatMoneyFromCents(metrics.net_revenue_cents) }}</p>
                <p class="mt-1 text-xs text-gray-500">Pagamentos menos reembolsos</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">Pedidos pagos</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ metrics.paid_orders }}</p>
                <p class="mt-1 text-xs text-gray-500">Ticket médio de {{ formatMoneyFromCents(metrics.average_ticket_cents) }}</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">Novos clientes</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ metrics.new_customers }}</p>
                <Link :href="route('admin.customers.index')" class="mt-1 inline-block text-xs font-medium text-indigo-600">Ver clientes</Link>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">Pagamentos pendentes</p>
                <p class="mt-2 text-3xl font-semibold text-yellow-700">{{ metrics.pending_payments }}</p>
                <Link :href="route('admin.orders.index', { status: 'pending_payment' })" class="mt-1 inline-block text-xs font-medium text-indigo-600">Revisar pedidos</Link>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">Reembolsos</p>
                <p class="mt-2 text-3xl font-semibold text-blue-700">{{ metrics.refunds_count }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ formatMoneyFromCents(metrics.refunds_amount_cents) }} no período</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <p class="text-sm text-gray-500">Operação pendente</p>
                <p class="mt-2 text-3xl font-semibold text-indigo-700">{{ operations.awaiting_fulfillment + operations.preparing }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ operations.awaiting_fulfillment }} aguardando · {{ operations.preparing }} em separação</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_320px]">
            <section class="overflow-hidden rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-center justify-between gap-3">
                    <div><h3 class="font-semibold text-gray-900">Receita diária</h3><p class="mt-1 text-sm text-gray-500">Valores líquidos dos pagamentos confirmados.</p></div>
                </div>
                <div class="mt-6 overflow-x-auto pb-2">
                    <div class="flex h-52 min-w-[700px] items-end gap-1 border-b border-gray-200 px-1">
                        <div v-for="(day, index) in daily_trend" :key="day.date" class="group relative flex h-full min-w-1 flex-1 items-end">
                            <div class="w-full rounded-t bg-indigo-500 transition hover:bg-indigo-600" :style="{ height: barHeight(day.revenue_cents) }"></div>
                            <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white group-hover:block">
                                {{ formatDate(day.date) }} · {{ formatMoneyFromCents(day.revenue_cents) }} · {{ day.orders }} pedido(s)
                            </div>
                            <span v-if="index === 0 || index === daily_trend.length - 1" class="absolute top-full mt-2 text-[10px] text-gray-500" :class="index === 0 ? 'left-0' : 'right-0'">{{ formatDate(day.date) }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <h3 class="font-semibold text-gray-900">Alertas operacionais</h3>
                <div class="mt-4 space-y-3">
                    <Link :href="route('admin.orders.index', { fulfillment_status: 'pending' })" class="flex items-center justify-between rounded-md bg-indigo-50 p-3 text-sm text-indigo-900 hover:bg-indigo-100"><span>Aguardando separação</span><strong>{{ operations.awaiting_fulfillment }}</strong></Link>
                    <Link :href="route('admin.orders.index', { fulfillment_status: 'preparing' })" class="flex items-center justify-between rounded-md bg-yellow-50 p-3 text-sm text-yellow-900 hover:bg-yellow-100"><span>Em separação</span><strong>{{ operations.preparing }}</strong></Link>
                    <Link :href="route('admin.inventory.index', { status: 'low_stock' })" class="flex items-center justify-between rounded-md bg-orange-50 p-3 text-sm text-orange-900 hover:bg-orange-100"><span>Estoque baixo</span><strong>{{ operations.low_stock }}</strong></Link>
                    <Link :href="route('admin.inventory.index', { status: 'out_of_stock' })" class="flex items-center justify-between rounded-md bg-red-50 p-3 text-sm text-red-900 hover:bg-red-100"><span>Sem estoque</span><strong>{{ operations.out_of_stock }}</strong></Link>
                </div>
            </aside>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4"><h3 class="font-semibold text-gray-900">Pedidos recentes</h3><Link :href="route('admin.orders.index')" class="text-sm font-medium text-indigo-600">Ver todos</Link></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Pedido</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Cliente</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Total</th></tr></thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="order in recent_orders" :key="order.id">
                                <td class="px-4 py-3 text-sm"><Link :href="route('admin.orders.show', order.id)" class="font-medium text-indigo-600">{{ order.number }}</Link><p class="text-xs text-gray-500">{{ formatDate(order.placed_at, true) }}</p></td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ order.customer.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600"><p>{{ order.status_label }}</p><p class="text-xs text-gray-500">{{ order.fulfillment_status_label }}</p></td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ formatMoneyFromCents(order.total_cents) }}</td>
                            </tr>
                            <tr v-if="recent_orders.length === 0"><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">Nenhum pedido registrado.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-gray-900">Atividade administrativa</h3>
                    <Link :href="route('admin.activity.index')" class="text-xs font-medium text-indigo-600">Ver histórico</Link>
                </div>
                <ul v-if="recent_activity.length" class="mt-4 divide-y divide-gray-200">
                    <li v-for="activity in recent_activity" :key="activity.id" class="py-3 first:pt-0">
                        <p class="text-sm text-gray-800">{{ activity.description }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ activity.user_name }} · {{ formatDate(activity.created_at, true) }}</p>
                    </li>
                </ul>
                <p v-else class="mt-4 text-sm text-gray-500">Nenhuma atividade operacional registrada.</p>
            </aside>
        </div>
    </AdminLayout>
</template>
