<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { OrderSummary } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    orders: OrderSummary[];
}>();

const formatDate = (isoDate?: string | null) => {
    if (!isoDate) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(isoDate));
};

const statusClasses = (status: string) => {
    if (status === 'paid') {
        return 'bg-green-100 text-green-800';
    }

    if (['payment_failed', 'cancelled', 'charged_back'].includes(status)) {
        return 'bg-red-100 text-red-800';
    }

    if (['refunded', 'partially_refunded'].includes(status)) {
        return 'bg-blue-100 text-blue-800';
    }

    return 'bg-yellow-100 text-yellow-800';
};
</script>

<template>
    <Head title="Meus pedidos" />

    <StoreLayout>
        <FlashAlert />

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Meus pedidos</h1>
            <p class="mt-2 text-gray-600">
                Acompanhe os pedidos realizados na loja.
            </p>
        </div>

        <div v-if="orders.length > 0" class="space-y-4">
            <article
                v-for="order in orders"
                :key="order.id"
                class="rounded-lg border border-gray-200 bg-white p-5"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-semibold text-gray-900">
                            {{ order.number }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ formatDate(order.placed_at) }}
                            · {{ order.item_count }}
                            {{ order.item_count === 1 ? 'item' : 'itens' }}
                        </p>
                        <span
                            class="mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="statusClasses(order.status)"
                        >
                            {{ order.status_label }}
                        </span>
                        <span
                            v-if="['paid', 'partially_refunded'].includes(order.status)"
                            class="ml-2 mt-2 inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800"
                        >
                            {{ order.fulfillment_status_label }}
                        </span>
                    </div>

                    <div class="text-left sm:text-right">
                        <p class="text-lg font-semibold text-gray-900">
                            {{ formatMoneyFromCents(order.total_cents) }}
                        </p>
                        <Link
                            :href="route('store.orders.show', order.id)"
                            class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Ver detalhes
                        </Link>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center"
        >
            <p class="text-gray-600">Voce ainda nao realizou pedidos.</p>
            <Link
                :href="route('store.home')"
                class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-800"
            >
                Continuar comprando
            </Link>
        </div>
    </StoreLayout>
</template>
