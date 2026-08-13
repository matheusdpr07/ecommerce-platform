<script setup lang="ts">
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { OrderSummary } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ orders: OrderSummary[] }>();

const formatDate = (isoDate?: string | null) =>
    isoDate
        ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'long' }).format(
              new Date(isoDate),
          )
        : '';
const statusClasses = (status: string) => {
    if (status === 'paid') return 'bg-emerald-100 text-emerald-800';
    if (['payment_failed', 'cancelled', 'charged_back'].includes(status))
        return 'bg-red-100 text-red-800';
    if (['refunded', 'partially_refunded'].includes(status))
        return 'bg-blue-100 text-blue-800';
    return 'bg-amber-100 text-amber-800';
};
</script>

<template>
    <Head title="Meus pedidos" />

    <StoreLayout>
        <div class="mb-10 border-b border-[var(--store-ink)]/15 pb-8">
            <p
                class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-coral)]"
            >
                Da escolha à sua porta
            </p>
            <h1
                class="mt-2 font-serif text-6xl leading-none tracking-[-0.06em] sm:text-7xl"
            >
                Meus pedidos
            </h1>
            <p class="mt-3 text-sm text-[var(--store-muted)]">
                Acompanhe cada etapa com clareza e volte aos detalhes quando quiser.
            </p>
        </div>

        <div v-if="orders.length > 0" class="space-y-4">
            <Link
                v-for="order in orders"
                :key="order.id"
                :href="route('store.orders.show', order.id)"
                view-transition
                class="store-reveal group grid gap-5 rounded-[1.75rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6 transition hover:-translate-y-0.5 hover:border-[var(--store-ink)] hover:shadow-xl sm:grid-cols-[1fr_auto] sm:items-center"
            >
                <span>
                    <span class="flex flex-wrap items-center gap-2">
                        <strong class="font-serif text-2xl tracking-[-0.03em]">
                            {{ order.number }}
                        </strong>
                        <span
                            class="rounded-full px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.1em]"
                            :class="statusClasses(order.status)"
                        >
                            {{ order.status_label }}
                        </span>
                        <span
                            v-if="['paid', 'partially_refunded'].includes(order.status)"
                            class="rounded-full bg-[var(--store-accent)] px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.1em]"
                        >
                            {{ order.fulfillment_status_label }}
                        </span>
                    </span>
                    <span class="mt-3 block text-sm text-[var(--store-muted)]">
                        {{ formatDate(order.placed_at) }} · {{ order.item_count }}
                        {{ order.item_count === 1 ? 'item' : 'itens' }}
                    </span>
                </span>
                <span class="flex items-center justify-between gap-8 sm:text-right">
                    <span class="font-serif text-2xl tracking-[-0.03em]">
                        {{ formatMoneyFromCents(order.total_cents) }}
                    </span>
                    <span
                        class="grid size-11 place-items-center rounded-full border border-[var(--store-ink)]/20 transition group-hover:translate-x-1 group-hover:bg-[var(--store-ink)] group-hover:text-[var(--store-cream)]"
                        aria-hidden="true"
                    >
                        →
                    </span>
                </span>
            </Link>
        </div>

        <div
            v-else
            class="rounded-[2.5rem] border border-dashed border-[var(--store-ink)]/25 bg-[var(--store-paper)] px-6 py-20 text-center"
        >
            <span
                class="mx-auto grid size-16 place-items-center rounded-full bg-[var(--store-accent)] text-2xl"
                aria-hidden="true"
            >
                ↗
            </span>
            <p class="mt-6 font-serif text-4xl tracking-[-0.04em]">
                Sua primeira história começa aqui
            </p>
            <p class="mt-2 text-sm text-[var(--store-muted)]">
                Você ainda não realizou pedidos nesta conta.
            </p>
            <Link
                :href="route('store.home')"
                view-transition
                class="mt-7 inline-flex rounded-full bg-[var(--store-ink)] px-7 py-3.5 text-sm font-bold text-[var(--store-cream)]"
            >
                Descobrir a loja
            </Link>
        </div>
    </StoreLayout>
</template>
