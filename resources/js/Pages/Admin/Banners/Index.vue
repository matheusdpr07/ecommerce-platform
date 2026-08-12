<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Paginated } from '@/types/catalog';
import type { Banner, BannerFilters } from '@/types/content';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    banners: Paginated<Banner>;
    filters: BannerFilters;
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const placement = ref(props.filters.placement);

const applyFilters = () => {
    router.get(
        route('admin.banners.index'),
        {
            search: search.value,
            status: status.value,
            placement: placement.value,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    placement.value = '';
    applyFilters();
};

const placementLabel = (value: Banner['placement']) =>
    value === 'hero' ? 'Destaque principal' : 'Bloco editorial';
</script>

<template>
    <Head title="Banners" />

    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Conteúdo da loja</p>
                    <h2
                        class="text-xl font-semibold leading-tight text-gray-800"
                    >
                        Banners
                    </h2>
                </div>
                <Link :href="route('admin.banners.create')">
                    <PrimaryButton>Novo banner</PrimaryButton>
                </Link>
            </div>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 p-4">
                <form
                    class="grid gap-3 md:grid-cols-[1fr_auto_auto_auto] md:items-end"
                    @submit.prevent="applyFilters"
                >
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Buscar
                        </label>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Título do banner"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Posição
                        </label>
                        <select
                            v-model="placement"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todas</option>
                            <option value="hero">Destaque principal</option>
                            <option value="editorial">Bloco editorial</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <select
                            v-model="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todos</option>
                            <option value="active">Ativos</option>
                            <option value="inactive">Inativos</option>
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
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Banner
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Posição
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Status
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                            >
                                Ordem
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500"
                            >
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="banner in banners.data" :key="banner.id">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="banner.image_url"
                                        :src="banner.image_url"
                                        :alt="banner.image_alt ?? ''"
                                        class="size-12 rounded-md object-cover"
                                    />
                                    <div
                                        v-else
                                        class="grid size-12 place-items-center rounded-md bg-lime-100 text-lime-800"
                                    >
                                        ✦
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ banner.title }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ banner.eyebrow || 'Sem chamada curta' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ placementLabel(banner.placement) }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        banner.is_active
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-700'
                                    "
                                >
                                    {{ banner.is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ banner.sort_order }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link
                                    :href="route('admin.banners.edit', banner.id)"
                                    class="font-medium text-indigo-600 hover:text-indigo-900"
                                >
                                    Editar
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="banners.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-sm text-gray-500"
                            >
                                Nenhum banner encontrado.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 p-4">
                <PaginationLinks :pagination="banners" />
            </div>
        </div>
    </AdminLayout>
</template>
