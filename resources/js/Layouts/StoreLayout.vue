<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import { userIsAdmin } from '@/utils/auth';
import { formatMoneyFromCents } from '@/utils/money';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const cart = computed(() => page.props.cart);
const wishlist = computed(() => page.props.wishlist);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <FlashAlert />

        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
            >
                <Link :href="route('store.home')" class="flex items-center gap-3">
                    <ApplicationLogo class="h-10 w-auto fill-current text-gray-800" />
                    <span class="text-lg font-semibold text-gray-900">Loja</span>
                </Link>

                <nav class="flex flex-wrap items-center gap-4 text-sm">
                    <Link
                        :href="route('store.home')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Produtos
                    </Link>
                    <Link
                        :href="route('store.cart.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Carrinho
                        <span
                            v-if="cart.item_count > 0"
                            class="ml-1 rounded-full bg-indigo-600 px-2 py-0.5 text-xs text-white"
                        >
                            {{ cart.item_count }}
                        </span>
                    </Link>
                    <Link
                        v-if="user"
                        :href="route('store.wishlist.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Favoritos
                        <span
                            v-if="wishlist.item_count > 0"
                            class="ml-1 rounded-full bg-gray-900 px-2 py-0.5 text-xs text-white"
                        >
                            {{ wishlist.item_count }}
                        </span>
                    </Link>
                    <Link
                        v-if="user"
                        :href="route('store.orders.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Pedidos
                    </Link>
                    <Link
                        v-if="user"
                        :href="route('store.addresses.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Enderecos
                    </Link>
                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Minha conta
                    </Link>
                    <Link
                        v-if="userIsAdmin(user)"
                        :href="route('admin.dashboard')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        Admin
                    </Link>
                    <Link
                        v-if="!user"
                        :href="route('login')"
                        class="rounded-md bg-gray-900 px-3 py-2 text-white hover:bg-gray-800"
                    >
                        Entrar
                    </Link>
                </nav>
            </div>

            <div
                v-if="cart.item_count > 0"
                class="border-t border-gray-100 bg-gray-50"
            >
                <div
                    class="mx-auto max-w-7xl px-4 py-2 text-sm text-gray-600 sm:px-6 lg:px-8"
                >
                    Subtotal do carrinho:
                    <span class="font-medium text-gray-900">
                        {{ formatMoneyFromCents(cart.subtotal_cents) }}
                    </span>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>

        <footer class="border-t border-gray-200 bg-white">
            <div
                class="mx-auto max-w-7xl px-4 py-6 text-center text-sm text-gray-500 sm:px-6 lg:px-8"
            >
                Plataforma de e-commerce em desenvolvimento.
            </div>
        </footer>
    </div>
</template>
