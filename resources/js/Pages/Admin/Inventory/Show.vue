<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    InventoryVariant,
    Paginated,
    StockMovementItem,
} from '@/types/catalog';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    variant: InventoryVariant;
    movements: Paginated<StockMovementItem>;
}>();

const adjustmentForm = useForm({
    operation: 'restock',
    quantity: 1,
    notes: '',
});

const thresholdForm = useForm({
    low_stock_threshold: props.variant.low_stock_threshold,
});

const submitAdjustment = () => {
    adjustmentForm.post(route('admin.inventory.adjust', props.variant.id), {
        preserveScroll: true,
        onSuccess: () => {
            adjustmentForm.quantity =
                adjustmentForm.operation === 'restock'
                    ? 1
                    : props.variant.stock_quantity;
            adjustmentForm.notes = '';
        },
    });
};

const submitThreshold = () => {
    thresholdForm.patch(route('admin.inventory.update', props.variant.id), {
        preserveScroll: true,
    });
};

const formatDate = (value?: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(value))
        : '';
</script>

<template>
    <Head :title="`Estoque - ${variant.product.name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ variant.product.name }} — {{ variant.name }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">SKU {{ variant.sku }}</p>
                </div>
                <Link :href="route('admin.inventory.index')">
                    <SecondaryButton>Voltar ao estoque</SecondaryButton>
                </Link>
            </div>
        </template>

        <FlashAlert />

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-lg bg-white p-6 shadow-sm lg:col-span-2">
                <div class="flex flex-wrap items-end justify-between gap-4 border-b border-gray-200 pb-5">
                    <div>
                        <p class="text-sm text-gray-500">Saldo atual</p>
                        <p class="mt-1 text-4xl font-semibold text-gray-900">{{ variant.stock_quantity }}</p>
                    </div>
                    <p class="text-sm text-gray-500">
                        O saldo só muda por uma movimentação registrada.
                    </p>
                </div>

                <form class="mt-6 space-y-5" @submit.prevent="submitAdjustment">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo de movimentação</label>
                        <select
                            v-model="adjustmentForm.operation"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="restock">Adicionar reposição ao saldo</option>
                            <option value="set">Definir o saldo correto</option>
                        </select>
                        <InputError class="mt-2" :message="adjustmentForm.errors.operation" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            {{ adjustmentForm.operation === 'restock' ? 'Quantidade a adicionar' : 'Novo saldo' }}
                        </label>
                        <input
                            v-model.number="adjustmentForm.quantity"
                            type="number"
                            min="0"
                            max="1000000"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError class="mt-2" :message="adjustmentForm.errors.quantity" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observação</label>
                        <textarea
                            v-model="adjustmentForm.notes"
                            rows="3"
                            placeholder="Ex.: entrada da nota fiscal 1234 ou correção após contagem"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError class="mt-2" :message="adjustmentForm.errors.notes" />
                    </div>

                    <PrimaryButton type="submit" :disabled="adjustmentForm.processing">
                        Registrar movimentação
                    </PrimaryButton>
                </form>
            </section>

            <aside class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900">Alerta de estoque</h3>
                <p class="mt-2 text-sm text-gray-500">
                    A variação será sinalizada quando o saldo for igual ou menor que este limite.
                </p>
                <form class="mt-5 space-y-4" @submit.prevent="submitThreshold">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Limite mínimo</label>
                        <input
                            v-model.number="thresholdForm.low_stock_threshold"
                            type="number"
                            min="0"
                            max="1000000"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <InputError class="mt-2" :message="thresholdForm.errors.low_stock_threshold" />
                    </div>
                    <PrimaryButton type="submit" :disabled="thresholdForm.processing">
                        Salvar limite
                    </PrimaryButton>
                </form>

                <div class="mt-6 border-t border-gray-200 pt-5 text-sm">
                    <p class="text-gray-500">Cadastro do produto</p>
                    <Link :href="route('admin.products.edit', variant.product.id)" class="mt-1 inline-block font-medium text-indigo-600 hover:text-indigo-800">
                        Editar produto e variação
                    </Link>
                </div>
            </aside>
        </div>

        <section class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <div class="border-b border-gray-200 px-5 py-4">
                <h3 class="font-semibold text-gray-900">Histórico de movimentações</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Motivo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Alteração</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Saldo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Responsável / observação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="movement in movements.data" :key="movement.id">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ formatDate(movement.created_at) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ movement.reason_label }}</td>
                            <td class="px-4 py-3 text-sm font-semibold" :class="movement.quantity_change > 0 ? 'text-green-700' : 'text-red-700'">
                                {{ movement.quantity_change > 0 ? '+' : '' }}{{ movement.quantity_change }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ movement.quantity_after }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <p>{{ movement.user?.name ?? 'Sistema' }}</p>
                                <p v-if="movement.notes" class="mt-1 text-xs text-gray-500">{{ movement.notes }}</p>
                            </td>
                        </tr>
                        <tr v-if="movements.data.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">Nenhuma movimentação registrada.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationLinks :pagination="movements" class="border-t border-gray-200 p-4" />
        </section>
    </AdminLayout>
</template>
