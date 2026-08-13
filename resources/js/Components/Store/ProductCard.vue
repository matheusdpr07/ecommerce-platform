<script setup lang="ts">
import type { StoreProductSummary } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    product: StoreProductSummary;
    index?: number;
}>();

const priceLabel = computed(() => {
    if (props.product.min_price_cents === props.product.max_price_cents) {
        return formatMoneyFromCents(props.product.min_price_cents);
    }

    return `A partir de ${formatMoneyFromCents(props.product.min_price_cents)}`;
});
</script>

<template>
    <article class="store-reveal group flex h-full flex-col">
        <Link
            :href="route('store.products.show', product.slug)"
            view-transition
            class="relative block aspect-[4/5] overflow-hidden rounded-[1.75rem] bg-[var(--store-sand)]"
        >
            <img
                v-if="product.primary_image"
                :src="product.primary_image.url"
                :alt="product.primary_image.alt_text ?? product.name"
                :loading="index === 0 ? 'eager' : 'lazy'"
                decoding="async"
                class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.035]"
            />
            <div
                v-else
                class="relative flex h-full items-center justify-center overflow-hidden"
            >
                <div
                    class="absolute -left-12 top-12 size-44 rounded-full bg-[var(--store-accent)]/70 blur-sm"
                />
                <div
                    class="absolute -bottom-16 -right-8 size-52 rounded-full bg-[var(--store-coral)]/55 blur-md"
                />
                <span
                    class="relative font-serif text-2xl italic text-[var(--store-ink)]/65"
                >
                    Imagem em breve
                </span>
            </div>

            <div
                class="absolute inset-x-4 top-4 flex items-start justify-between gap-3"
            >
                <span
                    v-if="product.has_promotion"
                    class="rounded-full bg-[var(--store-cocoa)] px-3 py-1.5 text-[0.62rem] font-bold uppercase tracking-[0.14em] text-[var(--store-cream)]"
                >
                    Oferta
                </span>
                <span
                    v-else-if="product.category"
                    class="rounded-full bg-[var(--store-paper)]/90 px-3 py-1.5 text-[0.62rem] font-bold uppercase tracking-[0.14em] backdrop-blur"
                >
                    {{ product.category.name }}
                </span>

                <span
                    v-if="!product.has_stock"
                    class="ml-auto rounded-full bg-[var(--store-ink)] px-3 py-1.5 text-[0.62rem] font-bold uppercase tracking-[0.14em] text-[var(--store-cream)]"
                >
                    Esgotado
                </span>
            </div>

            <span
                class="absolute bottom-4 left-4 right-4 flex translate-y-4 items-center justify-between rounded-full bg-[var(--store-ink)] px-5 py-3 text-sm font-semibold text-[var(--store-cream)] opacity-0 transition duration-300 group-hover:translate-y-0 group-hover:opacity-100"
            >
                Conhecer produto
                <span aria-hidden="true">↗</span>
            </span>
        </Link>

        <div class="flex flex-1 flex-col px-1 pt-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p
                        v-if="product.brand"
                        class="text-[0.62rem] font-semibold uppercase tracking-[0.18em] text-[var(--store-muted)]"
                    >
                        {{ product.brand.name }}
                    </p>
                    <Link
                        :href="route('store.products.show', product.slug)"
                        view-transition
                        class="mt-1 block font-serif text-[1.45rem] leading-tight tracking-[-0.025em] transition group-hover:opacity-60"
                    >
                        {{ product.name }}
                    </Link>
                </div>

                <span
                    class="mt-1 size-2 shrink-0 rounded-full"
                    :class="
                        product.has_stock
                            ? 'bg-emerald-600'
                            : 'bg-[var(--store-muted)]'
                    "
                    :title="product.has_stock ? 'Em estoque' : 'Esgotado'"
                />
            </div>

            <div class="mt-3 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                <span class="text-sm font-bold">{{ priceLabel }}</span>
                <span
                    v-if="product.has_promotion && product.min_original_price_cents"
                    class="text-xs text-[var(--store-muted)] line-through"
                >
                    {{ formatMoneyFromCents(product.min_original_price_cents) }}
                </span>
            </div>
        </div>
    </article>
</template>
