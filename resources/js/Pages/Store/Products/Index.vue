<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import ProductCard from '@/Components/Store/ProductCard.vue';
import StoreScrollStory from '@/Components/Store/StoreScrollStory.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type {
    Paginated,
    StoreCatalogFilters,
    StoreCategoryOption,
    StoreProductSummary,
} from '@/types/catalog';
import type { StoreBanner } from '@/types/content';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    products: Paginated<StoreProductSummary>;
    filters: StoreCatalogFilters;
    categories: StoreCategoryOption[];
    brands: StoreCategoryOption[];
    banners: StoreBanner[];
    activeCategory?: StoreCategoryOption | null;
}>();

const page = usePage();
const search = ref(props.filters.search);
const category = ref(props.filters.category);
const brand = ref(props.filters.brand);
const sort = ref(props.filters.sort);
const minPrice = ref(props.filters.min_price);
const maxPrice = ref(props.filters.max_price);
const filtersOpen = ref(false);

const pageTitle = computed(() => props.activeCategory?.name ?? 'Loja');
const hasActiveFilters = computed(
    () =>
        Boolean(
            props.filters.search ||
                props.filters.brand ||
                props.filters.min_price ||
                props.filters.max_price,
        ) || props.filters.sort !== 'name',
);
const showEditorial = computed(
    () => !props.activeCategory && !hasActiveFilters.value,
);
const activeFilterCount = computed(
    () =>
        [search.value, brand.value, minPrice.value, maxPrice.value].filter(
            Boolean,
        ).length + (sort.value !== 'name' ? 1 : 0),
);

const catalogRoute = () =>
    props.activeCategory
        ? route('store.categories.show', props.activeCategory.slug)
        : route('store.home');

const applyFilters = () => {
    filtersOpen.value = false;
    router.get(
        catalogRoute(),
        {
            search: search.value,
            category: category.value,
            brand: brand.value,
            sort: sort.value,
            min_price: minPrice.value,
            max_price: maxPrice.value,
        },
        {
            preserveState: true,
            replace: true,
            viewTransition: true,
        },
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
    <Head :title="pageTitle">
        <meta
            name="description"
            :content="
                activeCategory
                    ? `Explore a seleção ${activeCategory.name}.`
                    : page.props.store.tagline
            "
        />
    </Head>

    <StoreLayout immersive>
        <StoreScrollStory
            v-if="showEditorial"
            :banners="banners"
            :products="products.data"
            :categories="categories"
        />

        <section
            id="catalogo"
            class="scroll-mt-28 px-4 py-16 sm:px-6 lg:px-10 lg:py-24"
        >
            <div class="mx-auto max-w-[90rem]">
                <div
                    class="mb-10 flex flex-col gap-6 border-b border-[var(--store-ink)]/20 pb-8 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <p
                            class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-coral)]"
                        >
                            {{
                                activeCategory
                                    ? 'Seleção por categoria'
                                    : hasActiveFilters
                                      ? 'Resultado da busca'
                                      : 'Catálogo essencial'
                            }}
                        </p>
                        <component
                            :is="showEditorial ? 'h2' : 'h1'"
                            class="mt-2 font-serif text-5xl leading-none tracking-[-0.05em] sm:text-6xl"
                        >
                            {{ activeCategory?.name ?? 'Feito para descobrir' }}
                        </component>
                        <p
                            v-if="filters.search"
                            class="mt-3 text-sm text-[var(--store-muted)]"
                        >
                            Resultados para “{{ filters.search }}”
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-[var(--store-muted)]">
                            {{ products.total }}
                            {{ products.total === 1 ? 'produto' : 'produtos' }}
                        </span>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-[var(--store-ink)] px-5 py-2.5 text-sm font-bold lg:hidden"
                            @click="filtersOpen = !filtersOpen"
                        >
                            Filtros
                            <span
                                v-if="activeFilterCount > 0"
                                class="grid size-5 place-items-center rounded-full bg-[var(--store-coral)] text-[0.65rem] text-white"
                            >
                                {{ activeFilterCount }}
                            </span>
                        </button>
                    </div>
                </div>

                <button
                    v-if="filtersOpen"
                    type="button"
                    aria-label="Fechar filtros"
                    class="fixed inset-0 z-[70] bg-black/35 backdrop-blur-sm lg:hidden"
                    @click="filtersOpen = false"
                />

                <div class="grid gap-10 lg:grid-cols-[17rem_1fr] xl:gap-14">
                    <aside
                        class="h-fit bg-[var(--store-paper)] p-6 lg:sticky lg:top-28 lg:block lg:rounded-[1.75rem] lg:border lg:border-[var(--store-ink)]/10"
                        :class="
                            filtersOpen
                                ? 'fixed inset-x-4 top-24 z-[75] block max-h-[calc(100svh-7rem)] overflow-y-auto rounded-[1.75rem] shadow-2xl'
                                : 'hidden'
                        "
                        aria-label="Filtros do catálogo"
                    >
                        <div class="mb-6 flex items-center justify-between">
                            <h2 class="font-serif text-2xl">Refine sua busca</h2>
                            <button
                                type="button"
                                class="grid size-9 place-items-center rounded-full border border-[var(--store-ink)]/20 lg:hidden"
                                aria-label="Fechar filtros"
                                @click="filtersOpen = false"
                            >
                                ×
                            </button>
                        </div>

                        <form class="space-y-5" @submit.prevent="applyFilters">
                            <div>
                                <label
                                    for="catalog-search"
                                    class="text-[0.62rem] font-bold uppercase tracking-[0.17em]"
                                >
                                    Buscar
                                </label>
                                <input
                                    id="catalog-search"
                                    v-model="search"
                                    type="search"
                                    placeholder="Nome ou SKU"
                                    class="mt-2 block w-full rounded-xl border-[var(--store-line)] bg-transparent text-sm focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                                />
                            </div>

                            <div>
                                <label
                                    for="catalog-category"
                                    class="text-[0.62rem] font-bold uppercase tracking-[0.17em]"
                                >
                                    Categoria
                                </label>
                                <select
                                    id="catalog-category"
                                    v-model="category"
                                    class="mt-2 block w-full rounded-xl border-[var(--store-line)] bg-transparent text-sm focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
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
                                <label
                                    for="catalog-brand"
                                    class="text-[0.62rem] font-bold uppercase tracking-[0.17em]"
                                >
                                    Marca
                                </label>
                                <select
                                    id="catalog-brand"
                                    v-model="brand"
                                    class="mt-2 block w-full rounded-xl border-[var(--store-line)] bg-transparent text-sm focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
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

                            <fieldset>
                                <legend
                                    class="text-[0.62rem] font-bold uppercase tracking-[0.17em]"
                                >
                                    Faixa de preço
                                </legend>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <label class="sr-only" for="catalog-min-price">
                                        Preço mínimo
                                    </label>
                                    <input
                                        id="catalog-min-price"
                                        v-model="minPrice"
                                        inputmode="decimal"
                                        type="text"
                                        placeholder="Mínimo"
                                        class="block w-full rounded-xl border-[var(--store-line)] bg-transparent text-sm focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                                    />
                                    <label class="sr-only" for="catalog-max-price">
                                        Preço máximo
                                    </label>
                                    <input
                                        id="catalog-max-price"
                                        v-model="maxPrice"
                                        inputmode="decimal"
                                        type="text"
                                        placeholder="Máximo"
                                        class="block w-full rounded-xl border-[var(--store-line)] bg-transparent text-sm focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                                    />
                                </div>
                            </fieldset>

                            <div>
                                <label
                                    for="catalog-sort"
                                    class="text-[0.62rem] font-bold uppercase tracking-[0.17em]"
                                >
                                    Ordenar por
                                </label>
                                <select
                                    id="catalog-sort"
                                    v-model="sort"
                                    class="mt-2 block w-full rounded-xl border-[var(--store-line)] bg-transparent text-sm focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                                >
                                    <option value="name">Nome</option>
                                    <option value="price_asc">Menor preço</option>
                                    <option value="price_desc">Maior preço</option>
                                    <option value="newest">Mais recentes</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                class="w-full rounded-full bg-[var(--store-ink)] px-5 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5"
                            >
                                Aplicar filtros
                            </button>
                            <button
                                type="button"
                                class="w-full py-2 text-xs font-semibold text-[var(--store-muted)] underline decoration-[var(--store-line)] underline-offset-4"
                                @click="clearFilters"
                            >
                                Limpar seleção
                            </button>
                        </form>
                    </aside>

                    <section aria-live="polite">
                        <div
                            v-if="products.data.length > 0"
                            class="grid gap-x-5 gap-y-12 sm:grid-cols-2 xl:grid-cols-3"
                        >
                            <ProductCard
                                v-for="(product, index) in products.data"
                                :key="product.id"
                                :product="product"
                                :index="index"
                            />
                        </div>

                        <div
                            v-else
                            class="rounded-[2rem] border border-dashed border-[var(--store-ink)]/25 bg-[var(--store-paper)] p-12 text-center sm:p-20"
                        >
                            <span
                                class="mx-auto grid size-14 place-items-center rounded-full bg-[var(--store-accent)] text-xl"
                                aria-hidden="true"
                            >
                                ✦
                            </span>
                            <p class="mt-5 font-serif text-3xl">
                                Nenhum encontro por aqui
                            </p>
                            <p
                                class="mx-auto mt-2 max-w-md text-sm leading-6 text-[var(--store-muted)]"
                            >
                                Experimente ampliar a faixa de preço ou remover
                                alguns filtros para descobrir outras opções.
                            </p>
                            <button
                                type="button"
                                class="mt-6 rounded-full border border-[var(--store-ink)] px-5 py-2.5 text-sm font-bold"
                                @click="clearFilters"
                            >
                                Limpar filtros
                            </button>
                        </div>

                        <PaginationLinks
                            :pagination="products"
                            theme="store"
                        />
                    </section>
                </div>
            </div>
        </section>

        <section
            v-if="showEditorial"
            class="store-reveal bg-[var(--store-accent)] px-4 py-16 sm:px-6 lg:px-10 lg:py-24"
        >
            <div
                class="mx-auto grid max-w-[90rem] gap-10 lg:grid-cols-[1.4fr_1fr] lg:items-end"
            >
                <p
                    class="font-serif text-[clamp(3.2rem,7vw,7.5rem)] leading-[0.88] tracking-[-0.065em]"
                >
                    Comprar bem também é se sentir
                    <em class="text-[var(--store-coral)]">bem acompanhado.</em>
                </p>
                <div class="grid gap-6 sm:grid-cols-3 lg:grid-cols-1">
                    <div
                        v-for="item in [
                            ['01', 'Escolha livre', 'Explore antes de criar sua conta.'],
                            ['02', 'Pagamento seguro', 'Transações protegidas e status claro.'],
                            ['03', 'Do pedido à porta', 'Acompanhe cada etapa em um só lugar.'],
                        ]"
                        :key="item[0]"
                        class="grid grid-cols-[2.5rem_1fr] gap-3 border-t border-[var(--store-ink)]/30 pt-4"
                    >
                        <span class="text-xs font-bold">{{ item[0] }}</span>
                        <div>
                            <p class="font-bold">{{ item[1] }}</p>
                            <p class="mt-1 text-sm text-[var(--store-ink)]/65">
                                {{ item[2] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </StoreLayout>
</template>
