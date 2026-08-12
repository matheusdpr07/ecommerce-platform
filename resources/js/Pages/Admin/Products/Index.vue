<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    CatalogFilters,
    CategoryOption,
    Paginated,
    ProductListItem,
} from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    products: Paginated<ProductListItem>;
    filters: CatalogFilters;
    categories: CategoryOption[];
    brands: CategoryOption[];
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const categoryId = ref(props.filters.category_id ?? '');
const brandId = ref(props.filters.brand_id ?? '');

const applyFilters = () => {
    router.get(
        route('admin.products.index'),
        {
            search: search.value,
            status: status.value,
            category_id: categoryId.value,
            brand_id: brandId.value,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    categoryId.value = '';
    brandId.value = '';
    applyFilters();
};

const lowestPrice = (product: ProductListItem) => {
    const prices = product.variants.map((variant) => variant.price_cents);

    return prices.length > 0 ? Math.min(...prices) : 0;
};

const totalStock = (product: ProductListItem) =>
    product.variants.reduce(
        (total, variant) => total + variant.stock_quantity,
        0,
    );

const hasProducts = computed(() => props.products.data.length > 0);
</script>

<template>
    <Head title="Produtos" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Produtos
                </h2>
                <Link :href="route('admin.products.create')">
                    <PrimaryButton>Novo produto</PrimaryButton>
                </Link>
            </div>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 p-4">
                <form
                    class="grid gap-3 md:grid-cols-5 md:items-end"
                    @submit.prevent="applyFilters"
                >
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700"
                            >Buscar</label
                        >
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nome, slug ou SKU"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Categoria</label
                        >
                        <select
                            v-model="categoryId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todas</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </option>
                        </select>
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
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Produto
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Categoria
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Preco a partir de
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Estoque
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Status
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Acoes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                        >
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div class="font-medium">{{ product.name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ product.slug }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ product.category?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{
                                    formatMoneyFromCents(lowestPrice(product))
                                }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ totalStock(product) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        product.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    "
                                >
                                    {{
                                        product.is_active ? 'Ativo' : 'Inativo'
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link
                                    :href="
                                        route(
                                            'admin.products.edit',
                                            product.id,
                                        )
                                    "
                                    class="text-indigo-600 hover:text-indigo-900"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!hasProducts">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-sm text-gray-500"
                            >
                                Nenhum produto encontrado.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 p-4">
                <PaginationLinks :pagination="products" />
            </div>
        </div>
    </AdminLayout>
</template>
