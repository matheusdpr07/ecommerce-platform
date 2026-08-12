<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { AdminCustomerSummary, Paginated } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    customers: Paginated<AdminCustomerSummary>;
    filters: {
        search: string;
        verification: string;
        activity: string;
    };
}>();

const search = ref(props.filters.search);
const verification = ref(props.filters.verification);
const activity = ref(props.filters.activity);

const applyFilters = () => {
    router.get(
        route('admin.customers.index'),
        {
            search: search.value,
            verification: verification.value,
            activity: activity.value,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    verification.value = '';
    activity.value = '';
    applyFilters();
};

const formatDate = (value?: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short' }).format(
              new Date(value),
          )
        : '';
</script>

<template>
    <Head title="Clientes" />

    <AdminLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Clientes</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Consulta de cadastro, endereços e histórico de compras.
                </p>
            </div>
        </template>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <form class="grid gap-3 border-b border-gray-200 p-4 md:grid-cols-4 md:items-end" @submit.prevent="applyFilters">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Nome ou e-mail</label>
                    <input v-model="search" type="search" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <select v-model="verification" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        <option value="verified">Verificado</option>
                        <option value="unverified">Não verificado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Compras</label>
                    <select v-model="activity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        <option value="with_orders">Com pedidos</option>
                        <option value="without_orders">Sem pedidos</option>
                    </select>
                </div>
                <div class="flex gap-2 md:col-span-4">
                    <PrimaryButton type="submit">Filtrar</PrimaryButton>
                    <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="clearFilters">Limpar</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Cadastro</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Pedidos</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Total líquido</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Último pedido</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="customer in customers.data" :key="customer.id">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <p class="font-medium">{{ customer.name }}</p>
                                <p class="text-xs text-gray-500">{{ customer.email }}</p>
                                <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-xs" :class="customer.email_verified_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                    {{ customer.email_verified_at ? 'E-mail verificado' : 'E-mail pendente' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <p>{{ formatDate(customer.created_at) }}</p>
                                <p class="text-xs text-gray-500">{{ customer.addresses_count }} endereço(s)</p>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ customer.orders_count }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ formatMoneyFromCents(customer.net_spent_cents) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ customer.last_order_at ? formatDate(customer.last_order_at) : 'Nenhum' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link :href="route('admin.customers.show', customer.id)" class="font-medium text-indigo-600 hover:text-indigo-800">Ver detalhes</Link>
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">Nenhum cliente encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationLinks :pagination="customers" class="border-t border-gray-200 p-4" />
        </div>
    </AdminLayout>
</template>
