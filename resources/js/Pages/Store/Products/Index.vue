<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import ProductCard from '@/Components/Store/ProductCard.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type {
    Paginated,
    StoreCatalogFilters,
    StoreCategoryOption,
    StoreProductSummary,
} from '@/types/catalog';
import type { StoreBanner } from '@/types/content';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
const heroProduct = computed(() => props.products.data[0] ?? null);
const heroBanner = computed(
    () => props.banners.find((banner) => banner.placement === 'hero') ?? null,
);
const editorialBanners = computed(() =>
    props.banners.filter((banner) => banner.placement === 'editorial'),
);
const heroPrice = computed(() =>
    heroProduct.value
        ? formatMoneyFromCents(heroProduct.value.min_price_cents)
        : null,
);
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

const bannerThemeClasses = (theme: StoreBanner['theme']) =>
    ({
        paper: 'bg-[var(--store-paper)] text-[var(--store-ink)]',
        ink: 'bg-[var(--store-ink)] text-[var(--store-paper)]',
        accent: 'bg-[var(--store-accent)] text-[var(--store-ink)]',
    })[theme];

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
        <section
            v-if="showEditorial"
            class="relative isolate min-h-[calc(100svh-7.4rem)] overflow-hidden border-b border-[var(--store-ink)]/15"
        >
            <div
                class="absolute -left-[12vw] top-[20%] -z-10 size-[38vw] min-h-72 min-w-72 rounded-full bg-[var(--store-accent)] blur-3xl"
                aria-hidden="true"
            />
            <div
                class="absolute -right-[14vw] -top-[20%] -z-10 size-[44vw] min-h-80 min-w-80 rounded-full bg-[var(--store-coral)]/20 blur-3xl"
                aria-hidden="true"
            />

            <div
                class="mx-auto grid min-h-[calc(100svh-7.4rem)] w-full max-w-[90rem] items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-10 lg:py-16"
            >
                <div
                    class="relative z-10 min-w-0 w-full max-w-[calc(100vw-2rem)] sm:max-w-4xl"
                >
                    <p
                        class="mb-5 flex items-center gap-3 text-[0.67rem] font-bold uppercase tracking-[0.24em]"
                    >
                        <span class="h-px w-9 bg-current" />
                        {{
                            heroBanner?.eyebrow ??
                            'Nova perspectiva para o cotidiano'
                        }}
                    </p>
                    <h1
                        v-if="heroBanner"
                        class="max-w-4xl font-serif text-[clamp(3rem,8vw,8rem)] leading-[0.84] tracking-[-0.07em]"
                    >
                        {{ heroBanner.title }}
                    </h1>
                    <h1
                        v-else
                        class="font-serif text-[clamp(3rem,9vw,8.8rem)] leading-[0.8] tracking-[-0.075em]"
                    >
                        O extraordinário
                        <span class="block italic text-[var(--store-coral)] sm:ml-[0.34em]">
                            mora nos detalhes.
                        </span>
                    </h1>
                    <p
                        class="mt-8 w-full max-w-[calc(100vw-2rem)] break-words text-base leading-7 text-[var(--store-muted)] sm:max-w-lg sm:text-lg"
                    >
                        {{
                            heroBanner?.description ??
                            `${page.props.store.tagline} Uma seleção pensada para ser descoberta sem pressa e escolhida com confiança.`
                        }}
                    </p>
                    <div class="mt-9 flex flex-wrap items-center gap-3">
                        <a
                            v-if="heroBanner?.cta_url && heroBanner.cta_label"
                            :href="heroBanner.cta_url"
                            class="inline-flex items-center gap-4 rounded-full bg-[var(--store-ink)] px-6 py-3.5 text-sm font-bold text-white transition hover:-translate-y-0.5"
                        >
                            {{ heroBanner.cta_label }}
                            <span aria-hidden="true">↗</span>
                        </a>
                        <a
                            v-else
                            href="#catalogo"
                            class="inline-flex items-center gap-4 rounded-full bg-[var(--store-ink)] px-6 py-3.5 text-sm font-bold text-white transition hover:-translate-y-0.5"
                        >
                            Explorar seleção
                            <span aria-hidden="true">↓</span>
                        </a>
                        <Link
                            :href="route('store.home', { sort: 'newest' })"
                            view-transition
                            class="rounded-full border border-[var(--store-ink)]/25 px-6 py-3.5 text-sm font-bold transition hover:border-[var(--store-ink)]"
                        >
                            Ver novidades
                        </Link>
                    </div>
                </div>

                <div class="relative mx-auto min-w-0 w-full max-w-full sm:max-w-xl lg:justify-self-end">
                    <div
                        v-if="heroBanner?.image_url || heroProduct"
                        class="group relative block aspect-[4/5] w-[82%] overflow-hidden rounded-[3rem] bg-[#ded6c8] shadow-[0_30px_80px_rgba(23,24,17,0.18)] sm:w-[76%]"
                    >
                        <img
                            v-if="heroBanner?.image_url"
                            :src="heroBanner.image_url"
                            :alt="heroBanner.image_alt ?? heroBanner.title"
                            fetchpriority="high"
                            decoding="async"
                            class="h-full w-full object-cover transition duration-1000 group-hover:scale-[1.025]"
                        />
                        <img
                            v-else-if="heroProduct?.primary_image"
                            :src="heroProduct.primary_image.url"
                            :alt="
                                heroProduct.primary_image.alt_text ??
                                heroProduct.name
                            "
                            fetchpriority="high"
                            decoding="async"
                            class="h-full w-full object-cover transition duration-1000 group-hover:scale-[1.025]"
                        />
                        <div
                            v-else
                            class="relative h-full overflow-hidden bg-[#ddd5c7]"
                        >
                            <div
                                class="absolute -left-1/4 top-[12%] size-3/4 rounded-full bg-[var(--store-accent)]"
                            />
                            <div
                                class="absolute -bottom-[10%] -right-[18%] size-[82%] rounded-full bg-[var(--store-coral)]"
                            />
                            <div
                                class="absolute left-[16%] top-[24%] h-[55%] w-[68%] rotate-6 rounded-[45%_45%_20%_20%] border-[3px] border-[var(--store-ink)]/70 bg-white/35 backdrop-blur-sm"
                            />
                        </div>
                    </div>
                    <div
                        v-else
                        class="relative aspect-[4/5] w-[76%] overflow-hidden rounded-[3rem] bg-[#ddd5c7] shadow-[0_30px_80px_rgba(23,24,17,0.18)]"
                    >
                        <div
                            class="absolute -left-1/4 top-[12%] size-3/4 rounded-full bg-[var(--store-accent)]"
                        />
                        <div
                            class="absolute -bottom-[10%] -right-[18%] size-[82%] rounded-full bg-[var(--store-coral)]"
                        />
                    </div>

                    <Link
                        v-if="heroProduct && !heroBanner"
                        :href="route('store.products.show', heroProduct.slug)"
                        view-transition
                        class="absolute -bottom-5 right-0 w-[60%] rounded-[1.5rem] border border-[var(--store-ink)]/10 bg-[var(--store-paper)]/90 p-5 shadow-xl backdrop-blur-xl sm:right-3"
                    >
                        <span
                            class="text-[0.6rem] font-bold uppercase tracking-[0.2em] text-[var(--store-muted)]"
                        >
                            Em destaque
                        </span>
                        <span
                            class="mt-2 block font-serif text-xl leading-tight tracking-[-0.02em]"
                        >
                            {{ heroProduct.name }}
                        </span>
                        <span class="mt-2 block text-sm font-bold">
                            {{ heroPrice }}
                        </span>
                    </Link>

                    <div
                        class="absolute right-1 top-[9%] grid size-20 place-items-center rounded-full border border-[var(--store-ink)]/20 bg-[var(--store-accent)] text-center text-[0.55rem] font-bold uppercase leading-3 tracking-[0.13em] sm:size-24"
                        aria-hidden="true"
                    >
                        role para<br />descobrir<br />↓
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="showEditorial && editorialBanners.length > 0"
            class="px-4 py-10 sm:px-6 lg:px-10 lg:py-16"
            aria-label="Destaques editoriais"
        >
            <div class="mx-auto grid max-w-[90rem] gap-6 lg:grid-cols-2">
                <article
                    v-for="banner in editorialBanners"
                    :key="banner.id"
                    class="store-reveal relative isolate min-h-[28rem] overflow-hidden rounded-[2.5rem] p-7 sm:p-10"
                    :class="bannerThemeClasses(banner.theme)"
                >
                    <img
                        v-if="banner.image_url"
                        :src="banner.image_url"
                        :alt="banner.image_alt ?? banner.title"
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 -z-20 size-full object-cover"
                    />
                    <div
                        v-if="banner.image_url"
                        class="absolute inset-0 -z-10 bg-gradient-to-t from-black/75 via-black/15 to-transparent"
                    />
                    <div
                        class="flex min-h-[23rem] max-w-xl flex-col justify-end"
                        :class="banner.image_url ? 'text-white' : ''"
                    >
                        <p
                            v-if="banner.eyebrow"
                            class="text-[0.65rem] font-bold uppercase tracking-[0.22em] opacity-70"
                        >
                            {{ banner.eyebrow }}
                        </p>
                        <h2
                            class="mt-3 font-serif text-4xl leading-[0.95] tracking-[-0.045em] sm:text-5xl"
                        >
                            {{ banner.title }}
                        </h2>
                        <p
                            v-if="banner.description"
                            class="mt-4 max-w-md text-sm leading-6 opacity-75"
                        >
                            {{ banner.description }}
                        </p>
                        <a
                            v-if="banner.cta_url && banner.cta_label"
                            :href="banner.cta_url"
                            class="mt-6 inline-flex w-fit items-center gap-3 rounded-full border border-current px-5 py-2.5 text-sm font-bold"
                        >
                            {{ banner.cta_label }}
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                </article>
            </div>
        </section>

        <section
            v-if="showEditorial && categories.length > 0"
            class="border-b border-[var(--store-ink)]/15 bg-[var(--store-paper)]"
            aria-labelledby="category-heading"
        >
            <div
                class="mx-auto max-w-[90rem] px-4 py-14 sm:px-6 lg:px-10 lg:py-20"
            >
                <div
                    class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <p
                            class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-coral)]"
                        >
                            Comece por aqui
                        </p>
                        <h2
                            id="category-heading"
                            class="mt-2 font-serif text-4xl tracking-[-0.04em] sm:text-5xl"
                        >
                            Encontre o seu universo
                        </h2>
                    </div>
                    <span class="text-sm text-[var(--store-muted)]">
                        {{ categories.length }} categorias para explorar
                    </span>
                </div>

                <div
                    class="flex snap-x gap-3 overflow-x-auto pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <Link
                        v-for="(item, index) in categories"
                        :key="item.id"
                        :href="route('store.categories.show', item.slug)"
                        view-transition
                        class="group flex min-w-[17rem] snap-start items-center justify-between rounded-full border border-[var(--store-ink)]/20 px-6 py-4 transition hover:border-[var(--store-ink)] hover:bg-[var(--store-ink)] hover:text-white"
                    >
                        <span class="font-serif text-xl">{{ item.name }}</span>
                        <span class="flex items-center gap-3 text-xs">
                            0{{ index + 1 }}
                            <span
                                class="grid size-8 place-items-center rounded-full bg-[var(--store-accent)] text-[var(--store-ink)] transition group-hover:rotate-45"
                            >
                                ↗
                            </span>
                        </span>
                    </Link>
                </div>
            </div>
        </section>

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
                        <h1
                            class="mt-2 font-serif text-5xl leading-none tracking-[-0.05em] sm:text-6xl"
                        >
                            {{ activeCategory?.name ?? 'Feito para descobrir' }}
                        </h1>
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
