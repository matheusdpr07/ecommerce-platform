<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { Paginated } from '@/types/catalog';
import type { CustomerNotification } from '@/types/notification';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    customerNotifications: Paginated<CustomerNotification>;
}>();

const hasUnread = computed(() =>
    props.customerNotifications.data.some((notification) => !notification.read_at),
);

const markAllAsRead = () => {
    router.patch(
        route('store.notifications.read-all'),
        {},
        { preserveScroll: true },
    );
};

const formatDate = (isoDate?: string | null) => {
    if (!isoDate) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(isoDate));
};

const toneClasses = (tone: CustomerNotification['tone']) =>
    ({
        neutral: 'bg-[var(--store-paper)]',
        success: 'bg-emerald-50',
        danger: 'bg-red-50',
    })[tone];

const toneIcon = (notification: CustomerNotification) => {
    if (notification.tone === 'success') {
        return '✓';
    }

    if (notification.tone === 'danger') {
        return '!';
    }

    return '↗';
};
</script>

<template>
    <Head title="Notificações" />

    <StoreLayout>
        <div
            class="mb-10 flex flex-col gap-5 border-b border-[var(--store-ink)]/15 pb-8 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-coral)]"
                >
                    Tudo em um só lugar
                </p>
                <h1
                    class="mt-2 font-serif text-5xl leading-none tracking-[-0.05em] sm:text-6xl"
                >
                    Suas atualizações
                </h1>
                <p class="mt-3 text-sm text-[var(--store-muted)]">
                    Pagamentos, entregas e avaliações — sem perder nenhuma etapa.
                </p>
            </div>
            <button
                v-if="hasUnread"
                type="button"
                class="w-fit rounded-full border border-[var(--store-ink)] px-5 py-2.5 text-sm font-bold"
                @click="markAllAsRead"
            >
                Marcar todas como lidas
            </button>
        </div>

        <div v-if="customerNotifications.data.length > 0" class="space-y-3">
            <Link
                v-for="notification in customerNotifications.data"
                :key="notification.id"
                :href="route('store.notifications.update', notification.id)"
                method="patch"
                as="button"
                class="store-reveal group grid w-full grid-cols-[auto_1fr_auto] items-start gap-4 rounded-[1.5rem] border p-5 text-left transition hover:-translate-y-0.5 hover:shadow-lg sm:gap-6 sm:p-6"
                :class="[
                    toneClasses(notification.tone),
                    notification.read_at
                        ? 'border-[var(--store-ink)]/8 opacity-65'
                        : 'border-[var(--store-ink)]/20',
                ]"
            >
                <span
                    class="grid size-11 place-items-center rounded-full border border-[var(--store-ink)]/15 bg-[var(--store-paper)] text-lg font-bold"
                    aria-hidden="true"
                >
                    {{ toneIcon(notification) }}
                </span>
                <span>
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="font-serif text-xl font-semibold">
                            {{ notification.title }}
                        </span>
                        <span
                            v-if="!notification.read_at"
                            class="size-2 rounded-full bg-[var(--store-coral)]"
                            aria-label="Não lida"
                        />
                    </span>
                    <span
                        class="mt-2 block max-w-3xl text-sm leading-6 text-[var(--store-muted)]"
                    >
                        {{ notification.message }}
                    </span>
                    <time class="mt-3 block text-xs text-[var(--store-muted)]">
                        {{ formatDate(notification.created_at) }}
                    </time>
                </span>
                <span
                    class="mt-2 hidden text-sm font-bold transition group-hover:translate-x-1 sm:block"
                >
                    {{ notification.action_label ?? 'Abrir' }} →
                </span>
            </Link>
        </div>

        <div
            v-else
            class="rounded-[2rem] border border-dashed border-[var(--store-ink)]/25 bg-[var(--store-paper)] px-6 py-20 text-center"
        >
            <span
                class="mx-auto grid size-14 place-items-center rounded-full bg-[var(--store-accent)] text-xl"
                aria-hidden="true"
            >
                ✓
            </span>
            <p class="mt-5 font-serif text-3xl">Tudo tranquilo por aqui</p>
            <p class="mt-2 text-sm text-[var(--store-muted)]">
                Novidades sobre seus pedidos e avaliações aparecerão neste espaço.
            </p>
            <Link
                :href="route('store.home')"
                view-transition
                class="mt-6 inline-block rounded-full bg-[var(--store-ink)] px-6 py-3 text-sm font-bold text-[var(--store-cream)]"
            >
                Voltar à loja
            </Link>
        </div>

        <PaginationLinks
            :pagination="customerNotifications"
            theme="store"
        />
    </StoreLayout>
</template>
