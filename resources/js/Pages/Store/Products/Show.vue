<script setup lang="ts">
import ProductReviews from '@/Components/Store/ProductReviews.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { StoreProductDetail } from '@/types/catalog';
import type { ProductReviewsPayload } from '@/types/review';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    product: StoreProductDetail;
    is_in_wishlist: boolean;
    reviews: ProductReviewsPayload;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const selectedVariantId = ref<number>(
    props.product.variants.find((variant) => variant.in_stock)?.id ??
        props.product.variants[0]?.id ??
        0,
);
const selectedImageUrl = ref<string | null>(props.product.images[0]?.url ?? null);
const selectedVariant = computed(() =>
    props.product.variants.find(
        (variant) => variant.id === selectedVariantId.value,
    ),
);
const selectedImageAlt = computed(
    () =>
        props.product.images.find(
            (image) => image.url === selectedImageUrl.value,
        )?.alt_text ?? props.product.name,
);

const cartForm = useForm({
    product_variant_id: selectedVariantId.value,
    quantity: 1,
});
const wishlistForm = useForm({ product_id: props.product.id });

watch(selectedVariantId, (variantId) => {
    cartForm.product_variant_id = variantId;
    cartForm.quantity = Math.min(
        cartForm.quantity,
        selectedVariant.value?.stock_quantity ?? 1,
    );
});

const hasCompareAtDiscount = computed(() => {
    const variant = selectedVariant.value;

    return (
        variant?.compare_at_price_cents !== null &&
        variant?.compare_at_price_cents !== undefined &&
        variant.compare_at_price_cents > variant.price_cents
    );
});
const hasPromotion = computed(
    () => selectedVariant.value?.has_promotion ?? false,
);
const originalPriceCents = computed(() => {
    const variant = selectedVariant.value;

    if (hasPromotion.value && variant?.original_price_cents) {
        return variant.original_price_cents;
    }

    if (hasCompareAtDiscount.value && variant?.compare_at_price_cents) {
        return variant.compare_at_price_cents;
    }

    return null;
});
const canAddToCart = computed(
    () =>
        Boolean(selectedVariant.value?.in_stock) &&
        cartForm.quantity >= 1 &&
        cartForm.quantity <= (selectedVariant.value?.stock_quantity ?? 0),
);

const addToCart = () => {
    cartForm.product_variant_id = selectedVariantId.value;
    cartForm.post(route('store.cart.items.store'), { preserveScroll: true });
};

const toggleWishlist = () => {
    if (user.value && !props.is_in_wishlist) {
        wishlistForm.post(route('store.wishlist.items.store'), {
            preserveScroll: true,
        });
    }
};

const changeQuantity = (change: number) => {
    const max = selectedVariant.value?.stock_quantity ?? 1;
    cartForm.quantity = Math.max(1, Math.min(max, cartForm.quantity + change));
};
</script>

<template>
    <Head :title="product.meta_title ?? product.name">
        <meta
            v-if="product.meta_description || product.description"
            name="description"
            :content="product.meta_description ?? product.description ?? ''"
        />
    </Head>

    <StoreLayout>
        <nav
            class="mb-8 flex flex-wrap items-center gap-2 text-[0.65rem] font-semibold uppercase tracking-[0.15em] text-[var(--store-muted)]"
            aria-label="Navegação estrutural"
        >
            <Link :href="route('store.home')" view-transition>Catálogo</Link>
            <span aria-hidden="true">/</span>
            <Link
                v-if="product.category"
                :href="route('store.categories.show', product.category.slug)"
                view-transition
            >
                {{ product.category.name }}
            </Link>
            <span v-if="product.category" aria-hidden="true">/</span>
            <span class="text-[var(--store-ink)]">{{ product.name }}</span>
        </nav>

        <div class="grid min-w-0 gap-10 lg:grid-cols-[1.08fr_0.92fr] lg:gap-16">
            <section class="min-w-0 lg:sticky lg:top-28 lg:self-start">
                <div
                    class="group relative aspect-[4/5] w-full max-w-full overflow-hidden rounded-[2.5rem] bg-[#e7e0d5]"
                >
                    <img
                        v-if="selectedImageUrl"
                        :src="selectedImageUrl"
                        :alt="selectedImageAlt"
                        fetchpriority="high"
                        decoding="async"
                        class="size-full object-cover transition duration-700 group-hover:scale-[1.02]"
                    />
                    <div v-else class="relative size-full overflow-hidden">
                        <div
                            class="absolute -left-16 top-20 size-72 rounded-full bg-[var(--store-accent)]"
                        />
                        <div
                            class="absolute -bottom-20 -right-20 size-80 rounded-full bg-[var(--store-coral)]/70"
                        />
                        <span
                            class="absolute inset-0 grid place-items-center font-serif text-3xl italic text-[var(--store-ink)]/60"
                        >
                            Imagem em breve
                        </span>
                    </div>
                    <span
                        v-if="hasPromotion"
                        class="absolute left-5 top-5 rounded-full bg-[var(--store-coral)] px-4 py-2 text-[0.65rem] font-bold uppercase tracking-[0.15em] text-white"
                    >
                        Oferta especial
                    </span>
                </div>

                <div
                    v-if="product.images.length > 1"
                    class="mt-4 flex snap-x gap-3 overflow-x-auto pb-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <button
                        v-for="image in product.images"
                        :key="image.id"
                        type="button"
                        class="relative aspect-square w-20 shrink-0 snap-start overflow-hidden rounded-2xl border-2 transition sm:w-24"
                        :class="
                            selectedImageUrl === image.url
                                ? 'border-[var(--store-ink)]'
                                : 'border-transparent opacity-60 hover:opacity-100'
                        "
                        :aria-label="`Ver imagem: ${image.alt_text ?? product.name}`"
                        @click="selectedImageUrl = image.url"
                    >
                        <img
                            :src="image.url"
                            :alt="image.alt_text ?? product.name"
                            loading="lazy"
                            decoding="async"
                            class="size-full object-cover"
                        />
                    </button>
                </div>
            </section>

            <section class="min-w-0 lg:py-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <p
                        v-if="product.brand"
                        class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-[var(--store-coral)]"
                    >
                        {{ product.brand.name }}
                    </p>
                    <a
                        href="#avaliacoes"
                        class="flex items-center gap-2 text-xs text-[var(--store-muted)]"
                    >
                        <span aria-hidden="true">
                            <span class="text-[var(--store-coral)]">{{
                                '★'.repeat(Math.round(reviews.summary.average))
                            }}</span><span class="text-[var(--store-line)]">{{
                                '★'.repeat(5 - Math.round(reviews.summary.average))
                            }}</span>
                        </span>
                        {{ reviews.summary.average.toFixed(1) }} ·
                        {{ reviews.summary.total }} avaliações
                    </a>
                </div>

                <h1
                    class="mt-5 max-w-2xl font-serif text-[clamp(3.4rem,6vw,6.5rem)] leading-[0.87] tracking-[-0.065em]"
                >
                    {{ product.name }}
                </h1>

                <div v-if="selectedVariant" class="mt-8">
                    <div class="flex flex-wrap items-baseline gap-3">
                        <span
                            class="font-serif text-4xl tracking-[-0.04em] sm:text-5xl"
                        >
                            {{ formatMoneyFromCents(selectedVariant.price_cents) }}
                        </span>
                        <span
                            v-if="originalPriceCents"
                            class="text-base text-[var(--store-muted)] line-through"
                        >
                            {{ formatMoneyFromCents(originalPriceCents) }}
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-[var(--store-muted)]">
                        ou pague com Pix com confirmação segura · SKU
                        {{ selectedVariant.sku }}
                    </p>
                </div>

                <div
                    v-if="product.variants.length > 1"
                    class="mt-9 border-t border-[var(--store-ink)]/15 pt-6"
                >
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold">Escolha sua opção</h2>
                        <span class="text-xs text-[var(--store-muted)]">
                            {{ selectedVariant?.name }}
                        </span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            type="button"
                            class="rounded-full border px-5 py-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-35"
                            :class="
                                selectedVariantId === variant.id
                                    ? 'border-[var(--store-ink)] bg-[var(--store-ink)] text-white'
                                    : 'border-[var(--store-ink)]/20 hover:border-[var(--store-ink)]'
                            "
                            :disabled="!variant.in_stock"
                            @click="selectedVariantId = variant.id"
                        >
                            {{ variant.name }}
                        </button>
                    </div>
                </div>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <div
                        class="flex h-14 items-center justify-between rounded-full border border-[var(--store-ink)]/20 px-2 sm:w-36"
                    >
                        <button
                            type="button"
                            class="grid size-10 place-items-center rounded-full text-xl"
                            aria-label="Diminuir quantidade"
                            @click="changeQuantity(-1)"
                        >
                            −
                        </button>
                        <input
                            v-model.number="cartForm.quantity"
                            type="number"
                            min="1"
                            :max="selectedVariant?.stock_quantity ?? 1"
                            class="w-10 border-0 bg-transparent p-0 text-center text-sm font-bold focus:ring-0"
                            aria-label="Quantidade"
                        />
                        <button
                            type="button"
                            class="grid size-10 place-items-center rounded-full text-xl"
                            aria-label="Aumentar quantidade"
                            @click="changeQuantity(1)"
                        >
                            +
                        </button>
                    </div>
                    <button
                        type="button"
                        class="flex h-14 flex-1 items-center justify-between rounded-full bg-[var(--store-ink)] px-7 text-sm font-bold text-white transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-45"
                        :disabled="!canAddToCart || cartForm.processing"
                        @click="addToCart"
                    >
                        <span>
                            {{
                                selectedVariant?.in_stock
                                    ? 'Adicionar ao carrinho'
                                    : 'Produto indisponível'
                            }}
                        </span>
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
                <p
                    v-if="cartForm.errors.product_variant_id || cartForm.errors.quantity"
                    class="mt-2 text-sm text-red-600"
                >
                    {{ cartForm.errors.product_variant_id ?? cartForm.errors.quantity }}
                </p>

                <div class="mt-4 flex items-center justify-between gap-4 px-2">
                    <span
                        class="flex items-center gap-2 text-xs text-[var(--store-muted)]"
                    >
                        <span
                            class="size-2 rounded-full"
                            :class="
                                selectedVariant?.in_stock
                                    ? 'bg-emerald-600'
                                    : 'bg-[var(--store-muted)]'
                            "
                        />
                        {{
                            selectedVariant?.in_stock
                                ? `${selectedVariant.stock_quantity} disponível(is)`
                                : 'Fora de estoque'
                        }}
                    </span>
                    <Link
                        v-if="!user"
                        :href="route('login')"
                        class="text-xs font-bold underline underline-offset-4"
                    >
                        Entre para favoritar
                    </Link>
                    <button
                        v-else-if="!is_in_wishlist"
                        type="button"
                        class="text-xs font-bold underline underline-offset-4"
                        :disabled="wishlistForm.processing"
                        @click="toggleWishlist"
                    >
                        ♡ Salvar nos favoritos
                    </button>
                    <Link
                        v-else
                        :href="route('store.wishlist.index')"
                        class="text-xs font-bold underline underline-offset-4"
                    >
                        ♥ Salvo nos favoritos
                    </Link>
                </div>

                <div
                    class="mt-10 grid gap-3 border-y border-[var(--store-ink)]/15 py-6 sm:grid-cols-3"
                >
                    <div v-for="item in [
                        ['✓', 'Compra protegida'],
                        ['↗', 'Pedido rastreável'],
                        ['◇', 'Atendimento próximo'],
                    ]" :key="item[1]" class="flex items-center gap-2 text-xs font-semibold">
                        <span
                            class="grid size-8 place-items-center rounded-full bg-[var(--store-accent)]"
                        >
                            {{ item[0] }}
                        </span>
                        {{ item[1] }}
                    </div>
                </div>

                <div v-if="product.description" class="mt-9">
                    <p
                        class="text-[0.65rem] font-bold uppercase tracking-[0.18em] text-[var(--store-muted)]"
                    >
                        Sobre este produto
                    </p>
                    <p class="mt-4 whitespace-pre-line text-base leading-8">
                        {{ product.description }}
                    </p>
                </div>
            </section>
        </div>

        <ProductReviews :product-slug="product.slug" :reviews="reviews" />
    </StoreLayout>
</template>
