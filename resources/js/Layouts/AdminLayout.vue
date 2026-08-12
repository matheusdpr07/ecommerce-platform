<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { userIsAdmin } from '@/utils/auth';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user!);
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="border-b border-gray-100 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center">
                            <Link :href="route('admin.dashboard')">
                                <ApplicationLogo
                                    class="block h-9 w-auto fill-current text-gray-800"
                                />
                            </Link>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink
                                :href="route('admin.dashboard')"
                                :active="route().current('admin.dashboard')"
                            >
                                Painel
                            </NavLink>
                            <NavLink
                                :href="route('admin.categories.index')"
                                :active="route().current('admin.categories.*')"
                            >
                                Categorias
                            </NavLink>
                            <NavLink
                                :href="route('admin.brands.index')"
                                :active="route().current('admin.brands.*')"
                            >
                                Marcas
                            </NavLink>
                            <NavLink
                                :href="route('admin.products.index')"
                                :active="route().current('admin.products.*')"
                            >
                                Produtos
                            </NavLink>
                            <NavLink
                                :href="route('admin.coupons.index')"
                                :active="route().current('admin.coupons.*')"
                            >
                                Cupons
                            </NavLink>
                            <NavLink
                                :href="route('admin.promotions.index')"
                                :active="route().current('admin.promotions.*')"
                            >
                                Promocoes
                            </NavLink>
                            <NavLink
                                :href="route('admin.shipping-methods.index')"
                                :active="route().current('admin.shipping-methods.*')"
                            >
                                Frete
                            </NavLink>
                            <NavLink
                                v-if="userIsAdmin(page.props.auth.user)"
                                :href="route('dashboard')"
                            >
                                Area do cliente
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center">
                        <div class="relative ms-3">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                        >
                                            {{ user.name }}
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Sair
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="
                                showingNavigationDropdown =
                                    !showingNavigationDropdown
                            "
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                        >
                            <svg
                                class="h-6 w-6"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex':
                                            !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex':
                                            showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="sm:hidden"
            >
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        :href="route('admin.dashboard')"
                        :active="route().current('admin.dashboard')"
                    >
                        Painel
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        :href="route('admin.categories.index')"
                        :active="route().current('admin.categories.*')"
                    >
                        Categorias
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        :href="route('admin.brands.index')"
                        :active="route().current('admin.brands.*')"
                    >
                        Marcas
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        :href="route('admin.products.index')"
                        :active="route().current('admin.products.*')"
                    >
                        Produtos
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        :href="route('admin.coupons.index')"
                        :active="route().current('admin.coupons.*')"
                    >
                        Cupons
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        :href="route('admin.promotions.index')"
                        :active="route().current('admin.promotions.*')"
                    >
                        Promocoes
                    </ResponsiveNavLink>
                    <ResponsiveNavLink
                        :href="route('admin.shipping-methods.index')"
                        :active="route().current('admin.shipping-methods.*')"
                    >
                        Frete
                    </ResponsiveNavLink>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>
