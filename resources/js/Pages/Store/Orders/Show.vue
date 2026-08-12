<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { OrderDetail } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    order: OrderDetail;
}>();

const formatDate = (isoDate?: string | null) => {
    if (!isoDate) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(isoDate));
};
</script>

<template>
    <Head :title="`Pedido ${order.number}`" />

    <StoreLayout>
        <FlashAlert />

        <div class="mb-8">
            <Link
                :href="route('store.orders.index')"
                class="text-sm text-gray-600 hover:text-gray-900"
            >
                Voltar aos pedidos
            </Link>
            <h1 class="mt-4 text-3xl font-bold text-gray-900">
                Pedido {{ order.number }}
            </h1>
            <p class="mt-2 text-gray-600">
                Realizado em {{ formatDate(order.placed_at) }}
            </p>
            <span
                class="mt-3 inline-flex rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800"
            >
                {{ order.status_label }}
            </span>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
            <section class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Itens</h2>
                    <ul class="mt-4 divide-y divide-gray-200">
                        <li
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex flex-col gap-2 py-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <Link
                                    :href="route('store.products.show', item.product_slug)"
                                    class="font-medium text-gray-900 hover:text-indigo-700"
                                >
                                    {{ item.product_name }}
                                </Link>
                                <p class="text-sm text-gray-600">
                                    {{ item.variant_name }} · {{ item.variant_sku }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Qtd: {{ item.quantity }}
                                </p>
                            </div>
                            <div class="text-sm sm:text-right">
                                <p class="font-medium text-gray-900">
                                    {{ formatMoneyFromCents(item.line_total_cents) }}
                                </p>
                                <p class="text-gray-500">
                                    {{ formatMoneyFromCents(item.unit_price_cents) }} un.
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Entrega
                    </h2>
                    <dl class="mt-4 space-y-2 text-sm text-gray-600">
                        <div>
                            <dt class="font-medium text-gray-900">
                                {{ order.shipping_method_name }}
                            </dt>
                        </div>
                        <div>
                            <dt>{{ order.shipping_address.recipient_name }}</dt>
                            <dd>
                                {{ order.shipping_address.summary }}
                            </dd>
                            <dd v-if="order.shipping_address.complement">
                                {{ order.shipping_address.complement }}
                            </dd>
                            <dd>
                                CEP {{ order.shipping_address.postal_code }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <aside class="h-fit rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-gray-900">Resumo</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Subtotal</dt>
                        <dd>{{ formatMoneyFromCents(order.subtotal_cents) }}</dd>
                    </div>
                    <div
                        v-if="order.discount_cents > 0"
                        class="flex justify-between text-green-700"
                    >
                        <dt>
                            Desconto
                            <span v-if="order.coupon">({{ order.coupon.code }})</span>
                        </dt>
                        <dd>-{{ formatMoneyFromCents(order.discount_cents) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Frete</dt>
                        <dd>
                            {{
                                order.shipping_cents === 0
                                    ? 'Gratis'
                                    : formatMoneyFromCents(order.shipping_cents)
                            }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t border-gray-200 pt-2 font-semibold"
                    >
                        <dt>Total</dt>
                        <dd>{{ formatMoneyFromCents(order.total_cents) }}</dd>
                    </div>
                </dl>

                <div class="mt-4 rounded-md bg-blue-50 p-4 text-sm text-blue-800">
                    O pagamento sera habilitado na proxima fase.
                </div>
            </aside>
        </div>
    </StoreLayout>
</template>
