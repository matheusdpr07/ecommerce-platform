<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { CatalogFilters, Paginated, Promotion } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    promotions: Paginated<Promotion>;
    filters: CatalogFilters;
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const scopeLabels: Record<Promotion['scope'], string> = {
    all_products: 'Todos os produtos',
    category: 'Categoria',
    brand: 'Marca',
    product: 'Produto',
};

const applyFilters = () => {
    router.get(
        route('admin.promotions.index'),
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );
};

const formatValue = (promotion: Promotion) => {
    if (promotion.type === 'percentage') {
        return `${promotion.value}%`;
    }

    return formatMoneyFromCents(promotion.value);
};

const scopeTarget = (promotion: Promotion) => {
    if (promotion.scope === 'category') {
        return promotion.category?.name ?? '-';
    }

    if (promotion.scope === 'brand') {
        return promotion.brand?.name ?? '-';
    }

    if (promotion.scope === 'product') {
        return promotion.product?.name ?? '-';
    }

    return '-';
};

const destroyPromotion = (promotionId: number) => {
    router.delete(route('admin.promotions.destroy', promotionId));
};
</script>

<template>
    <Head title="Promocoes" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Promocoes
                </h2>
                <Link :href="route('admin.promotions.create')">
                    <PrimaryButton>Nova promocao</PrimaryButton>
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
                            placeholder="Nome da promocao"
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
                            <option value="active">Ativas</option>
                            <option value="inactive">Inativas</option>
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
                                Nome
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Escopo
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Alvo
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Desconto
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Prioridade
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                Acoes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="promotion in promotions.data"
                            :key="promotion.id"
                        >
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ promotion.name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ scopeLabels[promotion.scope] }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ scopeTarget(promotion) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ formatValue(promotion) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ promotion.priority }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link
                                    :href="
                                        route(
                                            'admin.promotions.edit',
                                            promotion.id,
                                        )
                                    "
                                    class="text-indigo-600 hover:text-indigo-800"
                                >
                                    Editar
                                </Link>
                                <button
                                    type="button"
                                    class="ml-3 text-red-600 hover:text-red-800"
                                    @click="destroyPromotion(promotion.id)"
                                >
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationLinks
                :pagination="promotions"
                class="border-t border-gray-200 p-4"
            />
        </div>
    </AdminLayout>
</template>
