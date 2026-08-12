<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { CatalogFilters, Coupon, Paginated } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    coupons: Paginated<Coupon>;
    filters: CatalogFilters;
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const applyFilters = () => {
    router.get(
        route('admin.coupons.index'),
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );
};

const formatValue = (coupon: Coupon) => {
    if (coupon.type === 'percentage') {
        return `${coupon.value}%`;
    }

    return formatMoneyFromCents(coupon.value);
};

const destroyCoupon = (couponId: number) => {
    router.delete(route('admin.coupons.destroy', couponId));
};
</script>

<template>
    <Head title="Cupons" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Cupons
                </h2>
                <Link :href="route('admin.coupons.create')">
                    <PrimaryButton>Novo cupom</PrimaryButton>
                </Link>
            </div>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 p-4">
                <form
                    class="flex flex-col gap-3 md:flex-row md:items-end"
                    @submit.prevent="applyFilters"
                >
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700"
                            >Buscar</label
                        >
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Codigo ou nome"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Status</label
                        >
                        <select
                            v-model="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="active">Ativos</option>
                            <option value="inactive">Inativos</option>
                        </select>
                    </div>
                    <PrimaryButton type="submit">Filtrar</PrimaryButton>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Codigo
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Nome
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Desconto
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Usos
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                Acoes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="coupon in coupons.data" :key="coupon.id">
                            <td class="px-4 py-3 font-mono text-sm text-gray-900">
                                {{ coupon.code }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ coupon.name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ formatValue(coupon) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ coupon.usage_count
                                }}<span v-if="coupon.usage_limit">
                                    / {{ coupon.usage_limit }}</span
                                >
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        coupon.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-600'
                                    "
                                >
                                    {{
                                        coupon.is_active ? 'Ativo' : 'Inativo'
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link
                                    :href="
                                        route('admin.coupons.edit', coupon.id)
                                    "
                                    class="text-indigo-600 hover:text-indigo-800"
                                >
                                    Editar
                                </Link>
                                <button
                                    type="button"
                                    class="ml-3 text-red-600 hover:text-red-800"
                                    @click="destroyCoupon(coupon.id)"
                                >
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationLinks :pagination="coupons" class="border-t border-gray-200 p-4" />
        </div>
    </AdminLayout>
</template>
