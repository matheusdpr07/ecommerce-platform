<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import StoreBrand from '@/Components/Store/StoreBrand.vue';
import { userIsAdmin } from '@/utils/auth';
import { formatMoneyFromCents } from '@/utils/money';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

withDefaults(
    defineProps<{
        immersive?: boolean;
    }>(),
    {
        immersive: false,
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const store = computed(() => page.props.store);
const cart = computed(() => page.props.cart);
const wishlist = computed(() => page.props.wishlist);
const menuOpen = ref(false);
const search = ref('');

const submitSearch = () => {
    if (!search.value.trim()) {
        return;
    }

    menuOpen.value = false;
    router.get(
        route('store.home'),
        { search: search.value.trim() },
        { viewTransition: true },
    );
};
</script>

<template>
    <div
        class="store-grain min-h-screen overflow-x-clip bg-[var(--store-canvas)] text-[var(--store-ink)]"
        @keyup.esc="menuOpen = false"
    >
        <div class="store-scroll-progress" aria-hidden="true" />

        <a
            href="#conteudo"
            class="fixed left-4 top-4 z-[90] -translate-y-24 rounded-full bg-[var(--store-accent)] px-5 py-3 text-sm font-bold text-[var(--store-ink)] transition focus:translate-y-0"
        >
            Pular para o conteúdo
        </a>

        <div
            class="overflow-hidden bg-[var(--store-ink)] py-2.5 text-[var(--store-paper)]"
            aria-label="Diferenciais da loja"
        >
            <div
                class="store-marquee-track flex w-max items-center whitespace-nowrap text-[0.66rem] font-semibold uppercase tracking-[0.22em]"
            >
                <div v-for="copy in 2" :key="copy" class="flex items-center">
                    <span class="mx-8">Pagamento protegido</span>
                    <span class="text-[var(--store-accent)]">✦</span>
                    <span class="mx-8">Acompanhamento em tempo real</span>
                    <span class="text-[var(--store-accent)]">✦</span>
                    <span class="mx-8">Atendimento próximo</span>
                    <span class="text-[var(--store-accent)]">✦</span>
                </div>
            </div>
        </div>

        <header
            class="sticky top-0 z-[60] border-b border-[var(--store-ink)]/10 bg-[color:rgba(244,240,232,0.88)] backdrop-blur-xl"
        >
            <div
                class="mx-auto flex h-[4.75rem] max-w-[90rem] items-center gap-4 px-4 sm:px-6 lg:px-10"
            >
                <button
                    type="button"
                    class="grid size-11 place-items-center rounded-full border border-[var(--store-ink)]/20 lg:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="menu-loja"
                    aria-label="Abrir menu"
                    @click="menuOpen = !menuOpen"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="size-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >
                        <path
                            :d="
                                menuOpen
                                    ? 'M5 5l14 14M19 5 5 19'
                                    : 'M4 7h16M4 12h16M4 17h16'
                            "
                        />
                    </svg>
                </button>

                <Link
                    :href="route('store.home')"
                    view-transition
                    class="shrink-0"
                    aria-label="Ir para a página inicial"
                >
                    <StoreBrand
                        :name="store.name"
                        :eyebrow="store.eyebrow"
                    />
                </Link>

                <nav
                    class="ml-8 hidden items-center gap-7 text-[0.7rem] font-semibold uppercase tracking-[0.15em] lg:flex"
                    aria-label="Navegação principal"
                >
                    <Link
                        :href="route('store.home')"
                        view-transition
                        class="border-b border-transparent py-2 transition hover:border-current"
                    >
                        Catálogo
                    </Link>
                    <Link
                        :href="route('store.home', { sort: 'newest' })"
                        view-transition
                        class="border-b border-transparent py-2 transition hover:border-current"
                    >
                        Novidades
                    </Link>
                    <Link
                        v-if="user"
                        :href="route('store.wishlist.index')"
                        view-transition
                        class="border-b border-transparent py-2 transition hover:border-current"
                    >
                        Favoritos
                        <span v-if="wishlist.item_count > 0">
                            ({{ wishlist.item_count }})
                        </span>
                    </Link>
                </nav>

                <form
                    class="ml-auto hidden w-full max-w-64 items-center border-b border-[var(--store-ink)]/35 focus-within:border-[var(--store-ink)] md:flex"
                    role="search"
                    @submit.prevent="submitSearch"
                >
                    <label for="store-search" class="sr-only">
                        Buscar no catálogo
                    </label>
                    <input
                        id="store-search"
                        v-model="search"
                        type="search"
                        placeholder="O que você procura?"
                        class="min-w-0 flex-1 border-0 bg-transparent px-0 py-2 text-sm placeholder:text-[var(--store-muted)] focus:ring-0"
                    />
                    <button
                        type="submit"
                        class="grid size-9 place-items-center"
                        aria-label="Buscar"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="size-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                        >
                            <circle cx="11" cy="11" r="6.5" />
                            <path d="m16 16 4 4" />
                        </svg>
                    </button>
                </form>

                <Link
                    :href="user ? route('dashboard') : route('login')"
                    view-transition
                    class="hidden size-11 place-items-center rounded-full transition hover:bg-white/60 sm:grid"
                    :aria-label="user ? 'Abrir minha conta' : 'Entrar na conta'"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="size-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >
                        <circle cx="12" cy="8" r="3.5" />
                        <path d="M5 20c.6-4 3-6 7-6s6.4 2 7 6" />
                    </svg>
                </Link>

                <Link
                    :href="route('store.cart.index')"
                    view-transition
                    class="relative grid size-11 place-items-center rounded-full bg-[var(--store-ink)] text-white transition hover:-translate-y-0.5"
                    aria-label="Abrir carrinho"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="size-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                    >
                        <path d="M5 8h14l-1 12H6L5 8Z" />
                        <path d="M9 9V6a3 3 0 0 1 6 0v3" />
                    </svg>
                    <span
                        v-if="cart.item_count > 0"
                        class="absolute -right-1 -top-1 grid size-5 place-items-center rounded-full bg-[var(--store-coral)] text-[0.62rem] font-bold text-white"
                    >
                        {{ cart.item_count }}
                    </span>
                </Link>
            </div>

            <div
                v-if="menuOpen"
                id="menu-loja"
                class="border-t border-[var(--store-ink)]/10 bg-[var(--store-canvas)] px-4 py-5 lg:hidden"
            >
                <form
                    class="mb-5 flex border-b border-[var(--store-ink)]"
                    role="search"
                    @submit.prevent="submitSearch"
                >
                    <label for="store-mobile-search" class="sr-only">
                        Buscar no catálogo
                    </label>
                    <input
                        id="store-mobile-search"
                        v-model="search"
                        type="search"
                        placeholder="Buscar no catálogo"
                        class="min-w-0 flex-1 border-0 bg-transparent px-0 focus:ring-0"
                    />
                    <button type="submit" class="px-3 text-sm font-bold">
                        Buscar
                    </button>
                </form>

                <nav
                    class="grid gap-1 text-sm font-semibold"
                    aria-label="Navegação móvel"
                >
                    <Link
                        :href="route('store.home')"
                        view-transition
                        class="border-b border-[var(--store-ink)]/10 py-3"
                    >
                        Catálogo
                    </Link>
                    <Link
                        :href="route('store.home', { sort: 'newest' })"
                        view-transition
                        class="border-b border-[var(--store-ink)]/10 py-3"
                    >
                        Novidades
                    </Link>
                    <Link
                        v-if="user"
                        :href="route('store.wishlist.index')"
                        view-transition
                        class="border-b border-[var(--store-ink)]/10 py-3"
                    >
                        Favoritos ({{ wishlist.item_count }})
                    </Link>
                    <Link
                        v-if="user"
                        :href="route('store.orders.index')"
                        view-transition
                        class="border-b border-[var(--store-ink)]/10 py-3"
                    >
                        Meus pedidos
                    </Link>
                    <Link
                        :href="user ? route('dashboard') : route('login')"
                        view-transition
                        class="border-b border-[var(--store-ink)]/10 py-3"
                    >
                        {{ user ? 'Minha conta' : 'Entrar na conta' }}
                    </Link>
                    <Link
                        v-if="userIsAdmin(user)"
                        :href="route('admin.dashboard')"
                        class="py-3 text-[var(--store-coral)]"
                    >
                        Administração
                    </Link>
                </nav>
            </div>

            <div
                v-if="cart.item_count > 0"
                class="border-t border-[var(--store-ink)]/10 bg-[var(--store-accent)]"
            >
                <div
                    class="mx-auto flex max-w-[90rem] items-center justify-between px-4 py-2 text-xs font-semibold sm:px-6 lg:px-10"
                >
                    <span>{{ cart.item_count }} item(ns) reservado(s)</span>
                    <span>
                        Subtotal
                        {{ formatMoneyFromCents(cart.subtotal_cents) }}
                    </span>
                </div>
            </div>
        </header>

        <div
            class="fixed right-4 top-28 z-[65] w-[calc(100%-2rem)] max-w-md sm:right-6"
        >
            <FlashAlert />
        </div>

        <main
            id="conteudo"
            :class="
                immersive
                    ? 'min-h-[60vh]'
                    : 'mx-auto min-h-[60vh] max-w-[90rem] px-4 py-10 sm:px-6 lg:px-10 lg:py-16'
            "
        >
            <slot />
        </main>

        <footer class="bg-[var(--store-ink)] text-[var(--store-paper)]">
            <div
                class="mx-auto grid max-w-[90rem] gap-12 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr] lg:px-10 lg:py-20"
            >
                <div>
                    <StoreBrand
                        :name="store.name"
                        :eyebrow="store.eyebrow"
                        inverse
                    />
                    <p
                        class="mt-7 max-w-md font-serif text-3xl leading-tight tracking-[-0.03em] text-white/90"
                    >
                        {{ store.tagline }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-[var(--store-accent)]"
                    >
                        Sua jornada
                    </p>
                    <nav class="mt-5 grid gap-3 text-sm text-white/70">
                        <Link :href="route('store.home')" view-transition>
                            Explorar produtos
                        </Link>
                        <Link
                            v-if="user"
                            :href="route('store.orders.index')"
                            view-transition
                        >
                            Acompanhar pedidos
                        </Link>
                        <Link
                            v-if="user"
                            :href="route('store.addresses.index')"
                            view-transition
                        >
                            Meus endereços
                        </Link>
                        <Link v-else :href="route('login')" view-transition>
                            Entrar na conta
                        </Link>
                    </nav>
                </div>

                <div>
                    <p
                        class="text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-[var(--store-accent)]"
                    >
                        Precisa de ajuda?
                    </p>
                    <p class="mt-5 max-w-xs text-sm leading-6 text-white/65">
                        Fale com a nossa equipe. Queremos que cada etapa da sua
                        compra seja simples e transparente.
                    </p>
                    <a
                        v-if="store.support_email"
                        :href="`mailto:${store.support_email}`"
                        class="mt-4 inline-block border-b border-white/30 pb-1 text-sm"
                    >
                        {{ store.support_email }}
                    </a>
                </div>
            </div>

            <div class="border-t border-white/10">
                <div
                    class="mx-auto flex max-w-[90rem] flex-col gap-2 px-4 py-5 text-xs text-white/45 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-10"
                >
                    <span>© {{ new Date().getFullYear() }} {{ store.name }}</span>
                    <span>Compra segura · experiência feita com cuidado</span>
                </div>
            </div>
        </footer>
    </div>
</template>
