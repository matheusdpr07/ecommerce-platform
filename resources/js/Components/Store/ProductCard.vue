<script setup lang="ts">
import type { StoreProductSummary } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    product: StoreProductSummary;
}>();

const priceLabel = computed(() => {
    if (props.product.min_price_cents === props.product.max_price_cents) {
        return formatMoneyFromCents(props.product.min_price_cents);
    }

    return `A partir de ${formatMoneyFromCents(props.product.min_price_cents)}`;
});
</script>

<template>
    <article
        class="flex h-full flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
    >
        <Link
            :href="route('store.products.show', product.slug)"
            class="block aspect-square overflow-hidden bg-gray-100"
        >
            <img
                v-if="product.primary_image"
                :src="product.primary_image.url"
                :alt="product.primary_image.alt_text ?? product.name"
                class="h-full w-full object-cover transition hover:scale-105"
            />
            <div
                v-else
                class="flex h-full items-center justify-center text-sm text-gray-400"
            >
                Sem imagem
            </div>
        </Link>

        <div class="flex flex-1 flex-col p-4">
            <p
                v-if="product.category"
                class="text-xs font-medium uppercase tracking-wide text-gray-500"
            >
                {{ product.category.name }}
            </p>

            <Link
                :href="route('store.products.show', product.slug)"
                class="mt-1 text-base font-semibold text-gray-900 hover:text-indigo-600"
            >
                {{ product.name }}
            </Link>

            <p v-if="product.brand" class="mt-1 text-sm text-gray-500">
                {{ product.brand.name }}
            </p>

            <div class="mt-auto flex items-center justify-between pt-4">
                <span class="text-lg font-bold text-gray-900">
                    {{ priceLabel }}
                </span>
                <span
                    class="rounded-full px-2 py-1 text-xs font-medium"
                    :class="
                        product.has_stock
                            ? 'bg-green-100 text-green-800'
                            : 'bg-gray-100 text-gray-600'
                    "
                >
                    {{ product.has_stock ? 'Em estoque' : 'Esgotado' }}
                </span>
            </div>
        </div>
    </article>
</template>
