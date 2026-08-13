<script setup lang="ts">
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { WishlistPayload } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{ wishlist: WishlistPayload }>();

const removeItem = (itemId: number) => {
    router.delete(route('store.wishlist.items.destroy', itemId), {
        preserveScroll: true,
    });
};
const moveToCart = (itemId: number) => {
    router.post(
        route('store.wishlist.items.move-to-cart', itemId),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Favoritos" />

    <StoreLayout>
        <div class="mb-10 border-b border-[var(--store-ink)]/15 pb-8">
            <p
                class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-coral)]"
            >
                Para reencontrar
            </p>
            <h1
                class="mt-2 font-serif text-6xl leading-none tracking-[-0.06em] sm:text-7xl"
            >
                Seus favoritos
            </h1>
            <p class="mt-3 text-sm text-[var(--store-muted)]">
                {{ wishlist.item_count }}
                {{ wishlist.item_count === 1 ? 'escolha guardada' : 'escolhas guardadas' }}
            </p>
        </div>

        <div
            v-if="wishlist.items.length > 0"
            class="grid gap-x-5 gap-y-12 sm:grid-cols-2 lg:grid-cols-3"
        >
            <article
                v-for="item in wishlist.items"
                :key="item.id"
                class="store-reveal group"
            >
                <Link
                    :href="route('store.products.show', item.product.slug)"
                    view-transition
                    class="relative block aspect-[4/5] overflow-hidden rounded-[1.75rem] bg-[var(--store-sand)]"
                >
                    <img
                        v-if="item.product.primary_image"
                        :src="item.product.primary_image.url"
                        :alt="item.product.primary_image.alt_text ?? item.product.name"
                        loading="lazy"
                        decoding="async"
                        class="size-full object-cover transition duration-700 group-hover:scale-[1.035]"
                    />
                    <span
                        v-if="!item.product.has_stock"
                        class="absolute left-4 top-4 rounded-full bg-[var(--store-ink)] px-3 py-1.5 text-[0.62rem] font-bold uppercase tracking-wider text-[var(--store-cream)]"
                    >
                        Esgotado
                    </span>
                </Link>

                <div class="px-1 pt-5">
                    <p
                        v-if="item.product.category"
                        class="text-[0.62rem] font-bold uppercase tracking-[0.18em] text-[var(--store-muted)]"
                    >
                        {{ item.product.category.name }}
                    </p>
                    <Link
                        :href="route('store.products.show', item.product.slug)"
                        view-transition
                        class="mt-1 block font-serif text-2xl tracking-[-0.03em]"
                    >
                        {{ item.product.name }}
                    </Link>
                    <p class="mt-2 text-sm font-bold">
                        {{ formatMoneyFromCents(item.product.min_price_cents) }}
                    </p>
                    <div class="mt-5 flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-full bg-[var(--store-ink)] px-5 py-3 text-sm font-bold text-[var(--store-cream)] disabled:opacity-40"
                            :disabled="!item.product.has_stock"
                            @click="moveToCart(item.id)"
                        >
                            {{ item.product.has_stock ? 'Mover para carrinho' : 'Sem estoque' }}
                        </button>
                        <button
                            type="button"
                            class="grid size-11 place-items-center rounded-full border border-[var(--store-ink)]/20 text-lg"
                            aria-label="Remover dos favoritos"
                            @click="removeItem(item.id)"
                        >
                            ×
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-else
            class="rounded-[2.5rem] border border-dashed border-[var(--store-ink)]/25 bg-[var(--store-paper)] px-6 py-20 text-center"
        >
            <span
                class="mx-auto grid size-16 place-items-center rounded-full bg-[var(--store-accent)] text-2xl"
                aria-hidden="true"
            >
                ♡
            </span>
            <p class="mt-6 font-serif text-4xl tracking-[-0.04em]">
                Guarde o que fizer seus olhos brilharem
            </p>
            <p class="mt-2 text-sm text-[var(--store-muted)]">
                Seus produtos favoritos aparecerão aqui.
            </p>
            <Link
                :href="route('store.home')"
                view-transition
                class="mt-7 inline-flex rounded-full bg-[var(--store-ink)] px-7 py-3.5 text-sm font-bold text-[var(--store-cream)]"
            >
                Explorar produtos
            </Link>
        </div>
    </StoreLayout>
</template>
