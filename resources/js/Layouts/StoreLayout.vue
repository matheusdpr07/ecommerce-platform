<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { userIsAdmin } from '@/utils/auth';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
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
