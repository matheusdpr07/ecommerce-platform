<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Brand, CatalogFilters, Paginated } from '@/types/catalog';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    brands: Paginated<Brand>;
    filters: CatalogFilters;
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const applyFilters = () => {
    router.get(
        route('admin.brands.index'),
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
};
</script>

<template>
    <Head title="Marcas" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Marcas
                </h2>
                <Link :href="route('admin.brands.create')">
                    <PrimaryButton>Nova marca</PrimaryButton>
                </Link>
            </div>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 p-4">
                <form
                    class="flex flex-col gap-3 md:flex-row md:items-end"
                    @submit.prevent="applyFilters"
                >
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700"
                            >Buscar</label
                        >
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nome ou slug"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Status</label
                        >
                        <select
                            v-model="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="active">Ativas</option>
                            <option value="inactive">Inativas</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <PrimaryButton type="submit">Filtrar</PrimaryButton>
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            @click="clearFilters"
                        >
                            Limpar
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Nome
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Slug
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">
                                Status
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">
                                Acoes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="brand in brands.data" :key="brand.id">
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ brand.name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ brand.slug }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        brand.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800'
                                    "
                                >
                                    {{ brand.is_active ? 'Ativa' : 'Inativa' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link
                                    :href="route('admin.brands.edit', brand.id)"
                                    class="text-indigo-600 hover:text-indigo-900"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="brands.data.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-sm text-gray-500"
                            >
                                Nenhuma marca encontrada.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 p-4">
                <PaginationLinks :pagination="brands" />
            </div>
        </div>
    </AdminLayout>
</template>
