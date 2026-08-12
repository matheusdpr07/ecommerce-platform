<script setup lang="ts">
import StoreLayout from '@/Layouts/StoreLayout.vue';
import { userIsAdmin } from '@/utils/auth';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user!);
const firstName = computed(() => user.value.name.split(' ')[0]);

const accountLinks = computed(() => [
    {
        title: 'Meus pedidos',
        description: 'Acompanhe pagamento, preparação e entrega.',
        href: route('store.orders.index'),
        symbol: '↗',
    },
    {
        title: 'Notificações',
        description: `${page.props.notifications.unread_count} atualização(ões) aguardando você.`,
        href: route('store.notifications.index'),
        symbol: '◉',
    },
    {
        title: 'Endereços',
        description: 'Organize os destinos usados no checkout.',
        href: route('store.addresses.index'),
        symbol: '⌖',
    },
    {
        title: 'Favoritos',
        description: 'Reencontre as escolhas que chamaram sua atenção.',
        href: route('store.wishlist.index'),
        symbol: '♡',
    },
    {
        title: 'Dados da conta',
        description: 'Atualize nome, e-mail, senha e preferências.',
        href: route('profile.edit'),
        symbol: '◇',
    },
]);
</script>

<template>
    <Head title="Minha conta" />

    <StoreLayout>
        <section
            class="relative isolate overflow-hidden rounded-[2.5rem] bg-[var(--store-ink)] px-6 py-12 text-[var(--store-paper)] sm:px-10 lg:px-14 lg:py-16"
        >
            <div
                class="absolute -right-24 -top-40 -z-10 size-[30rem] rounded-full bg-[var(--store-coral)]/60 blur-3xl"
            />
            <div
                class="absolute -bottom-52 left-1/3 -z-10 size-[28rem] rounded-full bg-[var(--store-accent)]/30 blur-3xl"
            />
            <p
                class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-accent)]"
            >
                Seu espaço
            </p>
            <h1
                class="mt-4 max-w-4xl font-serif text-[clamp(3.8rem,8vw,7.5rem)] leading-[0.84] tracking-[-0.065em]"
            >
                Que bom ter você aqui,
                <em class="text-[var(--store-accent)]">{{ firstName }}.</em>
            </h1>
            <p class="mt-7 max-w-lg text-sm leading-6 text-white/55">
                Acompanhe tudo o que acontece depois da escolha — de um único
                lugar, sem perder o caminho de volta à loja.
            </p>
        </section>

        <section
            class="grid gap-4 py-10 sm:grid-cols-2 lg:grid-cols-3 lg:py-14"
            aria-label="Recursos da conta"
        >
            <Link
                v-for="item in accountLinks"
                :key="item.title"
                :href="item.href"
                view-transition
                class="store-reveal group flex min-h-52 flex-col rounded-[1.75rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6 transition hover:-translate-y-1 hover:border-[var(--store-ink)] hover:shadow-xl"
            >
                <span
                    class="grid size-11 place-items-center rounded-full bg-[var(--store-accent)] text-lg transition group-hover:rotate-12"
                    aria-hidden="true"
                >
                    {{ item.symbol }}
                </span>
                <h2 class="mt-auto font-serif text-2xl tracking-[-0.03em]">
                    {{ item.title }}
                </h2>
                <p class="mt-2 text-sm leading-6 text-[var(--store-muted)]">
                    {{ item.description }}
                </p>
            </Link>

            <Link
                v-if="userIsAdmin(user)"
                :href="route('admin.dashboard')"
                class="store-reveal group flex min-h-52 flex-col rounded-[1.75rem] bg-[var(--store-coral)] p-6 text-white transition hover:-translate-y-1 hover:shadow-xl"
            >
                <span
                    class="grid size-11 place-items-center rounded-full border border-white/30 text-lg"
                    aria-hidden="true"
                >
                    ✦
                </span>
                <h2 class="mt-auto font-serif text-2xl tracking-[-0.03em]">
                    Administração
                </h2>
                <p class="mt-2 text-sm leading-6 text-white/70">
                    Entre na operação da loja, catálogo e conteúdo.
                </p>
            </Link>
        </section>

        <div
            class="flex flex-col gap-4 border-t border-[var(--store-ink)]/15 py-8 text-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-[var(--store-muted)]">Acesso como {{ user.email }}</p>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="w-fit font-bold underline underline-offset-4"
            >
                Sair da conta
            </Link>
        </div>
    </StoreLayout>
</template>
