<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
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

const selectedVariant = computed(() =>
    props.product.variants.find((variant) => variant.id === selectedVariantId.value),
);

const selectedImageUrl = ref<string | null>(
    props.product.images[0]?.url ?? null,
);

const cartForm = useForm({
    product_variant_id: selectedVariantId.value,
    quantity: 1,
});

const wishlistForm = useForm({
    product_id: props.product.id,
});

watch(selectedVariantId, (variantId) => {
    cartForm.product_variant_id = variantId;

    if (!selectedImageUrl.value && props.product.images[0]) {
        selectedImageUrl.value = props.product.images[0].url;
    }
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
    () => selectedVariant.value?.in_stock && cartForm.quantity >= 1,
);

const addToCart = () => {
    cartForm.product_variant_id = selectedVariantId.value;
    cartForm.post(route('store.cart.items.store'), {
        preserveScroll: true,
    });
};

const toggleWishlist = () => {
    if (!user.value) {
        return;
    }

    if (props.is_in_wishlist) {
        return;
    }

    wishlistForm.post(route('store.wishlist.items.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="product.meta_title ?? product.name" />

    <StoreLayout>
        <FlashAlert />

        <nav class="mb-6 text-sm text-gray-500">
            <Link :href="route('store.home')" class="hover:text-gray-900">
                Produtos
            </Link>
            <span class="mx-2">/</span>
            <Link
                v-if="product.category"
                :href="route('store.categories.show', product.category.slug)"
                class="hover:text-gray-900"
            >
                {{ product.category.name }}
            </Link>
            <span v-if="product.category" class="mx-2">/</span>
            <span class="text-gray-900">{{ product.name }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-2">
            <section>
                <div
                    class="aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-100"
                >
                    <img
                        v-if="selectedImageUrl"
                        :src="selectedImageUrl"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full items-center justify-center text-gray-400"
                    >
                        Sem imagem
                    </div>
                </div>

                <div
                    v-if="product.images.length > 1"
                    class="mt-4 grid grid-cols-4 gap-3"
                >
                    <button
                        v-for="image in product.images"
                        :key="image.id"
                        type="button"
                        class="overflow-hidden rounded-md border"
                        :class="
                            selectedImageUrl === image.url
                                ? 'border-indigo-500 ring-2 ring-indigo-200'
                                : 'border-gray-200'
                        "
                        @click="selectedImageUrl = image.url"
                    >
                        <img
                            :src="image.url"
                            :alt="image.alt_text ?? product.name"
                            class="aspect-square w-full object-cover"
                        />
                    </button>
                </div>
            </section>

            <section>
                <p
                    v-if="product.brand"
                    class="text-sm font-medium uppercase tracking-wide text-gray-500"
                >
                    {{ product.brand.name }}
                </p>

                <h1 class="mt-2 text-3xl font-bold text-gray-900">
                    {{ product.name }}
                </h1>

                <div v-if="selectedVariant" class="mt-6">
                    <div class="flex items-end gap-3">
                        <span class="text-3xl font-bold text-gray-900">
                            {{
                                formatMoneyFromCents(
                                    selectedVariant.price_cents,
                                )
                            }}
                        </span>
                        <span
                            v-if="originalPriceCents"
                            class="pb-1 text-lg text-gray-400 line-through"
                        >
                            {{
                                formatMoneyFromCents(originalPriceCents)
                            }}
                        </span>
                        <span
                            v-if="hasPromotion"
                            class="pb-1 text-sm font-medium text-red-600"
                        >
                            Promocao
                        </span>
                    </div>

                    <p class="mt-2 text-sm text-gray-500">
                        SKU: {{ selectedVariant.sku }}
                    </p>

                    <span
                        class="mt-3 inline-flex rounded-full px-3 py-1 text-sm font-medium"
                        :class="
                            selectedVariant.in_stock
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-600'
                        "
                    >
                        {{
                            selectedVariant.in_stock
                                ? `${selectedVariant.stock_quantity} em estoque`
                                : 'Indisponivel'
                        }}
                    </span>
                </div>

                <div v-if="product.variants.length > 1" class="mt-8">
                    <h2 class="text-sm font-medium text-gray-900">
                        Variacao
                    </h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="variant in product.variants"
                            :key="variant.id"
                            type="button"
                            class="rounded-md border px-4 py-2 text-sm transition"
                            :class="
                                selectedVariantId === variant.id
                                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
                            "
                            @click="selectedVariantId = variant.id"
                        >
                            {{ variant.name }}
                        </button>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-end gap-4">
                    <div>
                        <label
                            for="quantity"
                            class="block text-sm font-medium text-gray-700"
                            >Quantidade</label
                        >
                        <input
                            id="quantity"
                            v-model.number="cartForm.quantity"
                            type="number"
                            min="1"
                            :max="selectedVariant?.stock_quantity ?? 1"
                            class="mt-1 w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <PrimaryButton
                        type="button"
                        :disabled="!canAddToCart || cartForm.processing"
                        @click="addToCart"
                    >
                        Adicionar ao carrinho
                    </PrimaryButton>
                </div>

                <div class="mt-4">
                    <Link
                        v-if="!user"
                        :href="route('login')"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        Entre para salvar nos favoritos
                    </Link>
                    <button
                        v-else-if="!is_in_wishlist"
                        type="button"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                        :disabled="wishlistForm.processing"
                        @click="toggleWishlist"
                    >
                        Adicionar aos favoritos
                    </button>
                    <Link
                        v-else
                        :href="route('store.wishlist.index')"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Ver nos favoritos
                    </Link>
                </div>

                <div
                    v-if="product.description"
                    class="mt-8 rounded-lg border border-gray-200 bg-white p-6"
                >
                    <h2 class="text-lg font-semibold text-gray-900">
                        Descricao
                    </h2>
                    <p class="mt-3 whitespace-pre-line text-gray-600">
                        {{ product.description }}
                    </p>
                </div>
            </section>
        </div>

        <ProductReviews :product-slug="product.slug" :reviews="reviews" />
    </StoreLayout>
</template>
