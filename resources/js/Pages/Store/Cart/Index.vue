<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { CartPayload } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

defineProps<{
    cart: CartPayload;
}>();

const couponForm = useForm({
    code: '',
});

const updateQuantity = (itemId: number, quantity: number) => {
    router.patch(route('store.cart.items.update', itemId), { quantity });
};

const removeItem = (itemId: number) => {
    router.delete(route('store.cart.items.destroy', itemId));
};

const clearCart = () => {
    router.delete(route('store.cart.clear'));
};

const applyCoupon = () => {
    couponForm.post(route('store.cart.coupon.apply'), {
        preserveScroll: true,
        onSuccess: () => {
            couponForm.reset();
        },
    });
};

const removeCoupon = () => {
    router.delete(route('store.cart.coupon.remove'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Carrinho" />

    <StoreLayout>
        <FlashAlert />

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Carrinho</h1>
                <p class="mt-2 text-gray-600">
                    Revise os itens antes de continuar.
                </p>
            </div>
            <button
                v-if="cart.items.length > 0"
                type="button"
                class="text-sm text-red-600 hover:text-red-800"
                @click="clearCart"
            >
                Esvaziar carrinho
            </button>
        </div>

        <div
            v-if="cart.items.length > 0"
            class="grid gap-8 lg:grid-cols-[1fr_320px]"
        >
            <section class="space-y-4">
                <article
                    v-for="item in cart.items"
                    :key="item.id"
                    class="flex gap-4 rounded-lg border border-gray-200 bg-white p-4"
                >
                    <Link
                        :href="route('store.products.show', item.product.slug)"
                        class="h-24 w-24 shrink-0 overflow-hidden rounded-md bg-gray-100"
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

                    <div class="flex flex-1 flex-col justify-between">
                        <div>
                            <Link
                                :href="
                                    route(
                                        'store.products.show',
                                        item.product.slug,
                                    )
                                "
                                class="font-semibold text-gray-900 hover:text-indigo-600"
                            >
                                {{ item.product.name }}
                            </Link>
                            <p class="text-sm text-gray-500">
                                {{ item.variant.name }} · {{ item.variant.sku }}
                            </p>
                            <p
                                v-if="item.has_promotion"
                                class="mt-1 text-xs font-medium text-red-600"
                            >
                                Preco promocional aplicado
                            </p>
                            <p
                                v-if="!item.is_available"
                                class="mt-1 text-sm text-red-600"
                            >
                                Quantidade indisponivel em estoque.
                            </p>
                        </div>

                        <div
                            class="mt-3 flex flex-wrap items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600">Qtd.</label>
                                <input
                                    type="number"
                                    min="1"
                                    :max="item.max_quantity"
                                    :value="item.quantity"
                                    class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="
                                        updateQuantity(
                                            item.id,
                                            Number(
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                            ),
                                        )
                                    "
                                />
                            </div>

                            <div class="flex items-center gap-4">
                                <span class="font-semibold text-gray-900">
                                    {{
                                        formatMoneyFromCents(
                                            item.line_total_cents,
                                        )
                                    }}
                                </span>
                                <button
                                    type="button"
                                    class="text-sm text-red-600 hover:text-red-800"
                                    @click="removeItem(item.id)"
                                >
                                    Remover
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <aside class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Cupom de desconto
                    </h2>

                    <form
                        v-if="!cart.coupon"
                        class="mt-4 flex gap-2"
                        @submit.prevent="applyCoupon"
                    >
                        <input
                            v-model="couponForm.code"
                            type="text"
                            placeholder="Codigo do cupom"
                            class="block w-full rounded-md border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <PrimaryButton
                            type="submit"
                            :disabled="couponForm.processing"
                        >
                            Aplicar
                        </PrimaryButton>
                    </form>

                    <p
                        v-if="couponForm.errors.code"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ couponForm.errors.code }}
                    </p>

                    <div
                        v-if="cart.coupon"
                        class="mt-4 flex items-center justify-between rounded-md bg-green-50 px-3 py-2 text-sm"
                    >
                        <div>
                            <p class="font-medium text-green-800">
                                {{ cart.coupon.code }}
                            </p>
                            <p class="text-green-700">
                                -{{
                                    formatMoneyFromCents(
                                        cart.coupon.discount_cents,
                                    )
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="text-green-800 hover:text-green-900"
                            @click="removeCoupon"
                        >
                            Remover
                        </button>
                    </div>
                </div>

                <div
                    class="h-fit rounded-lg border border-gray-200 bg-white p-6"
                >
                    <h2 class="text-lg font-semibold text-gray-900">Resumo</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Itens</dt>
                            <dd class="font-medium text-gray-900">
                                {{ cart.item_count }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Subtotal</dt>
                            <dd class="font-medium text-gray-900">
                                {{ formatMoneyFromCents(cart.subtotal_cents) }}
                            </dd>
                        </div>
                        <div
                            v-if="cart.discount_cents > 0"
                            class="flex justify-between text-green-700"
                        >
                            <dt>Desconto</dt>
                            <dd class="font-medium">
                                -{{
                                    formatMoneyFromCents(cart.discount_cents)
                                }}
                            </dd>
                        </div>
                        <div
                            class="flex justify-between border-t border-gray-200 pt-2"
                        >
                            <dt class="font-medium text-gray-900">Total</dt>
                            <dd class="text-lg font-bold text-gray-900">
                                {{ formatMoneyFromCents(cart.total_cents) }}
                            </dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-xs text-gray-500">
                        Frete e checkout disponiveis nas proximas fases.
                    </p>
                </div>
            </aside>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center"
        >
            <p class="text-lg font-medium text-gray-900">
                Seu carrinho esta vazio
            </p>
            <p class="mt-2 text-sm text-gray-500">
                Explore os produtos e adicione itens para comecar.
            </p>
            <Link :href="route('store.home')" class="mt-6 inline-block">
                <PrimaryButton>Ver produtos</PrimaryButton>
            </Link>
        </div>
    </StoreLayout>
</template>
