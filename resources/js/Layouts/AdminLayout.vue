<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface NavigationItem {
    label: string;
    routeName: string;
    activePattern: string;
}

const navigationGroups: Array<{
    label: string;
    items: NavigationItem[];
}> = [
    {
        label: 'Visão geral',
        items: [
            {
                label: 'Painel',
                routeName: 'admin.dashboard',
                activePattern: 'admin.dashboard',
            },
        ],
    },
    {
        label: 'Operação',
        items: [
            {
                label: 'Pedidos',
                routeName: 'admin.orders.index',
                activePattern: 'admin.orders.*',
            },
            {
                label: 'Estoque',
                routeName: 'admin.inventory.index',
                activePattern: 'admin.inventory.*',
            },
            {
                label: 'Clientes',
                routeName: 'admin.customers.index',
                activePattern: 'admin.customers.*',
            },
            {
                label: 'Atividade',
                routeName: 'admin.activity.index',
                activePattern: 'admin.activity.*',
            },
        ],
    },
    {
        label: 'Catálogo',
        items: [
            {
                label: 'Produtos',
                routeName: 'admin.products.index',
                activePattern: 'admin.products.*',
            },
            {
                label: 'Categorias',
                routeName: 'admin.categories.index',
                activePattern: 'admin.categories.*',
            },
            {
                label: 'Marcas',
                routeName: 'admin.brands.index',
                activePattern: 'admin.brands.*',
            },
        ],
    },
    {
        label: 'Comercial',
        items: [
            {
                label: 'Cupons',
                routeName: 'admin.coupons.index',
                activePattern: 'admin.coupons.*',
            },
            {
                label: 'Promoções',
                routeName: 'admin.promotions.index',
                activePattern: 'admin.promotions.*',
            },
            {
                label: 'Frete',
                routeName: 'admin.shipping-methods.index',
                activePattern: 'admin.shipping-methods.*',
            },
        ],
    },
    {
        label: 'Conteúdo',
        items: [
            {
                label: 'Banners',
                routeName: 'admin.banners.index',
                activePattern: 'admin.banners.*',
            },
        ],
    },
];

const showingNavigation = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user!);

const itemClasses = (pattern: string) =>
    route().current(pattern)
        ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-100'
        : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900';
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-gray-200 bg-white lg:flex">
            <div class="flex h-16 shrink-0 items-center border-b border-gray-200 px-6">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-3">
                    <ApplicationLogo class="h-9 w-auto fill-current text-gray-800" />
                    <span class="text-sm font-semibold text-gray-800">Administração</span>
                </Link>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-5" aria-label="Navegação administrativa">
                <div v-for="group in navigationGroups" :key="group.label" class="mb-6">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ group.label }}</p>
                    <div class="mt-2 space-y-1">
                        <Link
                            v-for="item in group.items"
                            :key="item.routeName"
                            :href="route(item.routeName)"
                            class="block rounded-md px-3 py-2 text-sm font-medium transition"
                            :class="itemClasses(item.activePattern)"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </nav>

            <div class="border-t border-gray-200 p-4">
                <Link :href="route('dashboard')" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    Ir para a área do cliente
                </Link>
            </div>
        </aside>

        <div class="lg:pl-64">
            <nav class="border-b border-gray-200 bg-white">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 lg:hidden">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :aria-expanded="showingNavigation"
                            aria-label="Abrir menu administrativo"
                            @click="showingNavigation = !showingNavigation"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!showingNavigation" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <Link :href="route('admin.dashboard')"><ApplicationLogo class="h-8 w-auto fill-current text-gray-800" /></Link>
                    </div>

                    <p class="hidden text-sm font-medium text-gray-500 lg:block">Painel administrativo</p>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button type="button" class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                {{ user.name }}
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('dashboard')">Área do cliente</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Sair</DropdownLink>
                        </template>
                    </Dropdown>
                </div>

                <div v-if="showingNavigation" class="border-t border-gray-200 px-4 py-4 lg:hidden">
                    <div v-for="group in navigationGroups" :key="group.label" class="mb-5 last:mb-0">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ group.label }}</p>
                        <div class="mt-2 space-y-1">
                            <Link
                                v-for="item in group.items"
                                :key="item.routeName"
                                :href="route(item.routeName)"
                                class="block rounded-md px-3 py-2 text-sm font-medium"
                                :class="itemClasses(item.activePattern)"
                                @click="showingNavigation = false"
                            >
                                {{ item.label }}
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <header v-if="$slots.header" class="bg-white shadow-sm">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8"><slot name="header" /></div>
            </header>

            <main class="py-8 sm:py-10">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><slot /></div>
            </main>
        </div>
    </div>
</template>
