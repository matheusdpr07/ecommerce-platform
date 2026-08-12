<script setup lang="ts">
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Paginated } from '@/types/catalog';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface AuditLogItem {
    id: number;
    action: string;
    description: string;
    subject_type?: string | null;
    subject_id?: number | null;
    user?: { id: number; name: string; email: string } | null;
    created_at?: string | null;
}

const props = defineProps<{
    logs: Paginated<AuditLogItem>;
    filters: {
        search: string;
        action: string;
        date_from: string;
        date_to: string;
    };
    actions: string[];
}>();

const search = ref(props.filters.search);
const action = ref(props.filters.action);
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);

const applyFilters = () => {
    router.get(
        route('admin.activity.index'),
        {
            search: search.value,
            action: action.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    action.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const formatDate = (value?: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(value))
        : '';

const formatAction = (value: string) =>
    value
        .split('.')
        .map((part) => part.replaceAll('_', ' '))
        .join(' / ');
</script>

<template>
    <Head title="Atividade administrativa" />

    <AdminLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Atividade administrativa</h2>
                <p class="mt-1 text-sm text-gray-500">Histórico somente leitura das ações operacionais sensíveis.</p>
            </div>
        </template>

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <form class="grid gap-3 border-b border-gray-200 p-4 md:grid-cols-2 xl:grid-cols-6 xl:items-end" @submit.prevent="applyFilters">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Ação, descrição ou administrador</label>
                    <input v-model="search" type="search" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipo</label>
                    <select v-model="action" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        <option v-for="option in actions" :key="option" :value="option">{{ formatAction(option) }}</option>
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">De</label><input v-model="dateFrom" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" /></div>
                <div><label class="block text-sm font-medium text-gray-700">Até</label><input v-model="dateTo" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" /></div>
                <div class="flex gap-2"><PrimaryButton type="submit">Filtrar</PrimaryButton><button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="clearFilters">Limpar</button></div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Data</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Administrador</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Ação</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Registro</th><th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Descrição</th></tr></thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="log in logs.data" :key="log.id">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ formatDate(log.created_at) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700"><p class="font-medium text-gray-900">{{ log.user?.name ?? 'Administrador removido' }}</p><p v-if="log.user" class="text-xs text-gray-500">{{ log.user.email }}</p></td>
                            <td class="px-4 py-3 text-sm"><span class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-700">{{ log.action }}</span></td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ log.subject_type ? `${log.subject_type} #${log.subject_id}` : '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ log.description }}</td>
                        </tr>
                        <tr v-if="logs.data.length === 0"><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">Nenhuma atividade encontrada.</td></tr>
                    </tbody>
                </table>
            </div>
            <PaginationLinks :pagination="logs" class="border-t border-gray-200 p-4" />
        </div>
    </AdminLayout>
</template>
