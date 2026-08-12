<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    AdminOrderSummary,
    Paginated,
} from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    orders: Paginated<AdminOrderSummary>;
    filters: {
        search: string;
        status: string;
        fulfillment_status: string;
        date_from: string;
        date_to: string;
    };
    statuses: Array<{ value: string; label: string }>;
    fulfillmentStatuses: Array<{ value: string; label: string }>;
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const fulfillmentStatus = ref(props.filters.fulfillment_status);
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const applyFilters = () => {
    router.get(
        route('admin.orders.index'),
        {
            search: search.value,
            status: status.value,
            fulfillment_status: fulfillmentStatus.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    fulfillmentStatus.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const formatDate = (isoDate?: string | null) => {
    if (!isoDate) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(isoDate));
};

const statusClasses = (orderStatus: string) => {
    if (orderStatus === 'paid') {
        return 'bg-green-100 text-green-800';
    }

    if (['payment_failed', 'cancelled', 'charged_back'].includes(orderStatus)) {
        return 'bg-red-100 text-red-800';
    }

    if (['refunded', 'partially_refunded'].includes(orderStatus)) {
        return 'bg-blue-100 text-blue-800';
    }

    return 'bg-yellow-100 text-yellow-800';
};

const fulfillmentClasses = (fulfillmentStatusValue: string) => {
    if (fulfillmentStatusValue === 'delivered') {
        return 'bg-green-100 text-green-800';
    }

    if (fulfillmentStatusValue === 'shipped') {
        return 'bg-blue-100 text-blue-800';
    }

    if (fulfillmentStatusValue === 'cancelled') {
        return 'bg-gray-100 text-gray-700';
    }

    return 'bg-yellow-100 text-yellow-800';
};
</script>

<template>
    <Head title="Pedidos" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Pedidos
            </h2>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 p-4">
                <form
                    class="grid gap-3 md:grid-cols-2 xl:grid-cols-6 xl:items-end"
                    @submit.prevent="applyFilters"
                >
                    <div class="md:col-span-2 xl:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Buscar por pedido ou cliente
                        </label>
                        <input
                            v-model="search"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <select
                            v-model="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option
                                v-for="option in statuses"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Entrega
                        </label>
                        <select
                            v-model="fulfillmentStatus"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todas</option>
                            <option
                                v-for="option in fulfillmentStatuses"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            De
                        </label>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Até
                        </label>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div class="flex gap-2">
                        <PrimaryButton type="submit">Filtrar</PrimaryButton>
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            @click="clearFilters"
                        >
                            Limpar
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Pedido
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Cliente
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Pagamento
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Entrega
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Total
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                Acoes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="order in orders.data" :key="order.id">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <p class="font-medium">{{ order.number }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ formatDate(order.placed_at) }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <p>{{ order.customer.name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ order.customer.email }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="statusClasses(order.status)"
                                >
                                    {{ order.status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="fulfillmentClasses(order.fulfillment_status)"
                                >
                                    {{ order.fulfillment_status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ formatMoneyFromCents(order.total_cents) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link
                                    :href="route('admin.orders.show', order.id)"
                                    class="text-indigo-600 hover:text-indigo-800"
                                >
                                    Ver detalhes
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                Nenhum pedido encontrado.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationLinks
                :pagination="orders"
                class="border-t border-gray-200 p-4"
            />
        </div>
    </AdminLayout>
</template>
