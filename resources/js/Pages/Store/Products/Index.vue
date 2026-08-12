<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import ProductCard from '@/Components/Store/ProductCard.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type {
    Paginated,
    StoreCatalogFilters,
    StoreCategoryOption,
    StoreProductSummary,
} from '@/types/catalog';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    products: Paginated<StoreProductSummary>;
    filters: StoreCatalogFilters;
    categories: StoreCategoryOption[];
    brands: StoreCategoryOption[];
    activeCategory?: StoreCategoryOption | null;
}>();

const search = ref(props.filters.search);
const category = ref(props.filters.category);
const brand = ref(props.filters.brand);
const sort = ref(props.filters.sort);
const minPrice = ref(props.filters.min_price);
const maxPrice = ref(props.filters.max_price);

const pageTitle = props.activeCategory
    ? props.activeCategory.name
    : 'Produtos';

const applyFilters = () => {
    router.get(
        props.activeCategory
            ? route('store.categories.show', props.activeCategory.slug)
            : route('store.home'),
        {
            search: search.value,
            category: category.value,
            brand: brand.value,
            sort: sort.value,
            min_price: minPrice.value,
            max_price: maxPrice.value,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    category.value = props.activeCategory?.slug ?? '';
    brand.value = '';
    sort.value = 'name';
    minPrice.value = '';
    maxPrice.value = '';
    applyFilters();
};
</script>

<template>
    <Head :title="pageTitle" />

    <StoreLayout>
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ pageTitle }}</h1>
            <p class="mt-2 text-gray-600">
                Explore nosso catalogo. Navegue livremente, sem necessidade de
                cadastro.
            </p>
        </div>

        <div class="grid gap-8 lg:grid-cols-[280px_1fr]">
            <aside class="h-fit rounded-lg border border-gray-200 bg-white p-4">
                <form class="space-y-4" @submit.prevent="applyFilters">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Buscar</label
                        >
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nome ou SKU"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Categoria</label
                        >
                        <select
                            v-model="category"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :disabled="!!activeCategory"
                        >
                            <option value="">Todas</option>
                            <option
                                v-for="item in categories"
                                :key="item.id"
                                :value="item.slug"
                            >
                                {{ item.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Marca</label
                        >
                        <select
                            v-model="brand"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todas</option>
                            <option
                                v-for="item in brands"
                                :key="item.id"
                                :value="item.slug"
                            >
                                {{ item.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Preco min.</label
                            >
                            <input
                                v-model="minPrice"
                                type="text"
                                placeholder="0,00"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Preco max.</label
                            >
                            <input
                                v-model="maxPrice"
                                type="text"
                                placeholder="999,00"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Ordenar por</label
                        >
                        <select
                            v-model="sort"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="name">Nome</option>
                            <option value="price_asc">Menor preco</option>
                            <option value="price_desc">Maior preco</option>
                            <option value="newest">Mais recentes</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <PrimaryButton type="submit" class="w-full justify-center">
                            Filtrar
                        </PrimaryButton>
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            @click="clearFilters"
                        >
                            Limpar
                        </button>
                    </div>
                </form>
            </aside>

            <section>
                <div
                    v-if="products.data.length > 0"
                    class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <ProductCard
                        v-for="product in products.data"
                        :key="product.id"
                        :product="product"
                    />
                </div>

                <div
                    v-else
                    class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center"
                >
                    <p class="text-lg font-medium text-gray-900">
                        Nenhum produto encontrado
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        Tente ajustar os filtros ou buscar por outro termo.
                    </p>
                </div>

                <PaginationLinks :pagination="products" />
            </section>
        </div>
    </StoreLayout>
</template>
