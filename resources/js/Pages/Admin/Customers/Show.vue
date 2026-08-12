<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    AdminCustomerDetail,
    AdminCustomerOrder,
    Paginated,
} from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    customer: AdminCustomerDetail;
    orders: Paginated<AdminCustomerOrder>;
    summary: {
        orders_count: number;
        paid_orders_count: number;
        net_spent_cents: number;
        average_ticket_cents: number;
    };
}>();

const formatDate = (value?: string | null, withTime = false) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              ...(withTime ? { timeStyle: 'short' as const } : {}),
          }).format(new Date(value))
        : '';
</script>

<template>
    <Head :title="`Cliente ${customer.name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ customer.name }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ customer.email }}</p>
                </div>
                <Link :href="route('admin.customers.index')"><SecondaryButton>Voltar aos clientes</SecondaryButton></Link>
            </div>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Pedidos</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ summary.orders_count }}</p></div>
            <div class="rounded-lg bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Compras pagas</p><p class="mt-2 text-3xl font-semibold text-gray-900">{{ summary.paid_orders_count }}</p></div>
            <div class="rounded-lg bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Total líquido</p><p class="mt-2 text-2xl font-semibold text-green-700">{{ formatMoneyFromCents(summary.net_spent_cents) }}</p></div>
            <div class="rounded-lg bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Ticket médio</p><p class="mt-2 text-2xl font-semibold text-gray-900">{{ formatMoneyFromCents(summary.average_ticket_cents) }}</p></div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[320px_1fr]">
            <aside class="space-y-6">
                <section class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Cadastro</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-gray-500">Criado em</dt><dd class="text-gray-900">{{ formatDate(customer.created_at) }}</dd></div>
                        <div><dt class="text-gray-500">E-mail</dt><dd :class="customer.email_verified_at ? 'text-green-700' : 'text-yellow-700'">{{ customer.email_verified_at ? `Verificado em ${formatDate(customer.email_verified_at)}` : 'Não verificado' }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="font-semibold text-gray-900">Endereços</h3>
                    <div v-if="customer.addresses.length" class="mt-4 space-y-4">
                        <article v-for="address in customer.addresses" :key="address.id" class="border-t border-gray-200 pt-4 first:border-0 first:pt-0">
                            <div class="flex items-center gap-2"><p class="text-sm font-medium text-gray-900">{{ address.label }}</p><span v-if="address.is_default" class="rounded bg-indigo-100 px-1.5 py-0.5 text-xs text-indigo-700">Principal</span></div>
                            <p class="mt-1 text-sm text-gray-600">{{ address.recipient_name }}</p>
                            <p class="text-sm text-gray-600">{{ address.line1 }}</p>
                            <p class="text-sm text-gray-600">{{ address.line2 }}</p>
                            <p class="text-sm text-gray-600">CEP {{ address.postal_code }}</p>
                        </article>
                    </div>
                    <p v-else class="mt-4 text-sm text-gray-500">Nenhum endereço salvo.</p>
                </section>
            </aside>

            <section class="h-fit overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 px-5 py-4"><h3 class="font-semibold text-gray-900">Histórico de pedidos</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Pedido</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Pagamento</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Entrega</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Total</th><th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Ação</th></tr></thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="order in orders.data" :key="order.id">
                                <td class="px-4 py-3 text-sm text-gray-900"><p class="font-medium">{{ order.number }}</p><p class="text-xs text-gray-500">{{ formatDate(order.placed_at, true) }}</p></td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ order.status_label }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ order.fulfillment_status_label }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ formatMoneyFromCents(order.total_cents) }}</td>
                                <td class="px-4 py-3 text-right text-sm"><Link :href="route('admin.orders.show', order.id)" class="font-medium text-indigo-600 hover:text-indigo-800">Abrir</Link></td>
                            </tr>
                            <tr v-if="orders.data.length === 0"><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">Nenhum pedido realizado.</td></tr>
                        </tbody>
                    </table>
                </div>
                <PaginationLinks :pagination="orders" class="border-t border-gray-200 p-4" />
            </section>
        </div>
    </AdminLayout>
</template>
