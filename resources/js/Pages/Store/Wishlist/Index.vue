<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { WishlistPayload } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{
    wishlist: WishlistPayload;
}>();

const removeItem = (itemId: number) => {
    router.delete(route('store.wishlist.items.destroy', itemId));
};

const moveToCart = (itemId: number) => {
    router.post(route('store.wishlist.items.move-to-cart', itemId));
};
</script>

<template>
    <Head title="Favoritos" />

    <StoreLayout>
        <FlashAlert />

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Lista de desejos</h1>
            <p class="mt-2 text-gray-600">
                Salve produtos para comprar depois.
            </p>
        </div>

        <div
            v-if="wishlist.items.length > 0"
            class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="item in wishlist.items"
                :key="item.id"
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
            >
                <Link
                    :href="route('store.products.show', item.product.slug)"
                    class="block aspect-square bg-gray-100"
                >
                    <img
                        v-if="item.product.primary_image"
                        :src="item.product.primary_image.url"
                        :alt="
                            item.product.primary_image.alt_text ??
                            item.product.name
                        "
                        class="h-full w-full object-cover"
                    />
                </Link>

                <div class="p-4">
                    <p
                        v-if="item.product.category"
                        class="text-xs uppercase tracking-wide text-gray-500"
                    >
                        {{ item.product.category.name }}
                    </p>
                    <Link
                        :href="route('store.products.show', item.product.slug)"
                        class="mt-1 block font-semibold text-gray-900 hover:text-indigo-600"
                    >
                        {{ item.product.name }}
                    </Link>
                    <p class="mt-2 font-bold text-gray-900">
                        {{
                            formatMoneyFromCents(item.product.min_price_cents)
                        }}
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <PrimaryButton
                            type="button"
                            :disabled="!item.product.has_stock"
                            @click="moveToCart(item.id)"
                        >
                            {{
                                item.product.has_stock
                                    ? 'Mover para carrinho'
                                    : 'Sem estoque'
                            }}
                        </PrimaryButton>
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            @click="removeItem(item.id)"
                        >
                            Remover
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center"
        >
            <p class="text-lg font-medium text-gray-900">
                Nenhum favorito ainda
            </p>
            <p class="mt-2 text-sm text-gray-500">
                Salve produtos pela pagina de detalhes.
            </p>
            <Link :href="route('store.home')" class="mt-6 inline-block">
                <PrimaryButton>Ver produtos</PrimaryButton>
            </Link>
        </div>
    </StoreLayout>
</template>
