<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { InventoryVariant, Paginated } from '@/types/catalog';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    variants: Paginated<InventoryVariant>;
    filters: { search: string; status: string };
    summary: {
        total: number;
        in_stock: number;
        low_stock: number;
        out_of_stock: number;
    };
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const applyFilters = () => {
    router.get(
        route('admin.inventory.index'),
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
};

const statusLabel = (stockStatus: InventoryVariant['stock_status']) =>
    ({
        in_stock: 'Em estoque',
        low_stock: 'Estoque baixo',
        out_of_stock: 'Sem estoque',
        inactive: 'Inativo',
    })[stockStatus];

const statusClasses = (stockStatus: InventoryVariant['stock_status']) =>
    ({
        in_stock: 'bg-green-100 text-green-800',
        low_stock: 'bg-yellow-100 text-yellow-800',
        out_of_stock: 'bg-red-100 text-red-800',
        inactive: 'bg-gray-100 text-gray-700',
    })[stockStatus];

const formatDate = (value?: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(value))
        : '';
</script>

<template>
    <Head title="Estoque" />

    <AdminLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Estoque
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Acompanhe saldos, alertas e movimentações por variação.
                </p>
            </div>
        </template>

        <FlashAlert />

        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <button
                type="button"
                class="rounded-lg bg-white p-5 text-left shadow-sm ring-1 ring-gray-200 hover:ring-indigo-300"
                @click="status = ''; applyFilters()"
            >
                <p class="text-sm text-gray-500">Variações cadastradas</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">
                    {{ summary.total }}
                </p>
            </button>
            <button
                type="button"
                class="rounded-lg bg-white p-5 text-left shadow-sm ring-1 ring-gray-200 hover:ring-green-300"
                @click="status = 'in_stock'; applyFilters()"
            >
                <p class="text-sm text-gray-500">Em estoque</p>
                <p class="mt-2 text-3xl font-semibold text-green-700">
                    {{ summary.in_stock }}
                </p>
            </button>
            <button
                type="button"
                class="rounded-lg bg-white p-5 text-left shadow-sm ring-1 ring-gray-200 hover:ring-yellow-300"
                @click="status = 'low_stock'; applyFilters()"
            >
                <p class="text-sm text-gray-500">Estoque baixo</p>
                <p class="mt-2 text-3xl font-semibold text-yellow-700">
                    {{ summary.low_stock }}
                </p>
            </button>
            <button
                type="button"
                class="rounded-lg bg-white p-5 text-left shadow-sm ring-1 ring-gray-200 hover:ring-red-300"
                @click="status = 'out_of_stock'; applyFilters()"
            >
                <p class="text-sm text-gray-500">Sem estoque</p>
                <p class="mt-2 text-3xl font-semibold text-red-700">
                    {{ summary.out_of_stock }}
                </p>
            </button>
        </div>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <form
                class="grid gap-3 border-b border-gray-200 p-4 md:grid-cols-[minmax(0,1fr)_220px_auto] md:items-end"
                @submit.prevent="applyFilters"
            >
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Produto, variação ou SKU
                    </label>
                    <input
                        v-model="search"
                        type="search"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Situação
                    </label>
                    <select
                        v-model="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Todas</option>
                        <option value="in_stock">Em estoque</option>
                        <option value="low_stock">Estoque baixo</option>
                        <option value="out_of_stock">Sem estoque</option>
                        <option value="inactive">Inativos</option>
                    </select>
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

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Produto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Saldo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Situação</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Última movimentação</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="variant in variants.data" :key="variant.id">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <p class="font-medium">{{ variant.product.name }}</p>
                                <p class="text-xs text-gray-500">{{ variant.name }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ variant.sku }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <span class="font-semibold">{{ variant.stock_quantity }}</span>
                                <span class="text-xs text-gray-500"> / alerta em {{ variant.low_stock_threshold }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusClasses(variant.stock_status)">
                                    {{ statusLabel(variant.stock_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <template v-if="variant.latest_movement">
                                    <p>{{ variant.latest_movement.reason_label }} ({{ variant.latest_movement.quantity_change > 0 ? '+' : '' }}{{ variant.latest_movement.quantity_change }})</p>
                                    <p class="text-xs text-gray-500">{{ formatDate(variant.latest_movement.created_at) }}</p>
                                </template>
                                <span v-else class="text-gray-400">Sem movimentações</span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link :href="route('admin.inventory.show', variant.id)" class="font-medium text-indigo-600 hover:text-indigo-800">
                                    Gerenciar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="variants.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                Nenhuma variação encontrada.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationLinks :pagination="variants" class="border-t border-gray-200 p-4" />
        </div>
    </AdminLayout>
</template>
