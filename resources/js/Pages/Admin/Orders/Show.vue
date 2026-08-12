<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { AdminOrderDetail } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    order: AdminOrderDetail;
    canRefund: boolean;
    nextFulfillmentStatus?: { value: string; label: string } | null;
}>();

const refundForm = useForm({});
const fulfillmentForm = useForm({
    fulfillment_status:
        props.nextFulfillmentStatus?.value ?? props.order.fulfillment_status,
    tracking_code: props.order.tracking_code ?? '',
    tracking_url: props.order.tracking_url ?? '',
});
const trackingForm = useForm({
    fulfillment_status: 'shipped',
    tracking_code: props.order.tracking_code ?? '',
    tracking_url: props.order.tracking_url ?? '',
});
const notesForm = useForm({
    internal_notes: props.order.internal_notes ?? '',
});

const refund = () => {
    if (!window.confirm('Confirma o reembolso integral deste pedido?')) {
        return;
    }

    refundForm.post(route('admin.orders.refund', props.order.id), {
        preserveScroll: true,
    });
};

const advanceFulfillment = () => {
    fulfillmentForm.patch(
        route('admin.orders.fulfillment.update', props.order.id),
        { preserveScroll: true },
    );
};

const updateTracking = () => {
    trackingForm.patch(
        route('admin.orders.fulfillment.update', props.order.id),
        { preserveScroll: true },
    );
};

const updateNotes = () => {
    notesForm.patch(route('admin.orders.notes.update', props.order.id), {
        preserveScroll: true,
    });
};

const formatDate = (isoDate?: string | null) => {
    if (!isoDate) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'long',
        timeStyle: 'short',
    }).format(new Date(isoDate));
};

const nextActionLabel = (status: string) =>
    ({
        preparing: 'Iniciar separação',
        shipped: 'Marcar como enviado',
        delivered: 'Confirmar entrega',
    })[status] ?? 'Avançar pedido';
</script>

<template>
    <Head :title="`Pedido ${order.number}`" />

    <AdminLayout>
        <template #header>
            <div>
                <Link
                    :href="route('admin.orders.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Voltar aos pedidos
                </Link>
                <h2 class="mt-2 text-xl font-semibold leading-tight text-gray-800">
                    Pedido {{ order.number }}
                </h2>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-medium">
                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">
                        Pagamento: {{ order.status_label }}
                    </span>
                    <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-indigo-800">
                        Entrega: {{ order.fulfillment_status_label }}
                    </span>
                </div>
            </div>
        </template>

        <FlashAlert />

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <section class="space-y-6">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Operação e entrega</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                As etapas avançam em sequência e não alteram o estado financeiro.
                            </p>
                        </div>
                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800">
                            {{ order.fulfillment_status_label }}
                        </span>
                    </div>

                    <ol class="mt-6 grid gap-3 sm:grid-cols-4">
                        <li class="rounded-md border border-gray-200 p-3">
                            <p class="text-xs font-medium uppercase text-gray-500">Pedido pago</p>
                            <p class="mt-1 text-sm text-gray-700">{{ order.payment?.paid_at ? formatDate(order.payment.paid_at) : 'Pendente' }}</p>
                        </li>
                        <li class="rounded-md border border-gray-200 p-3">
                            <p class="text-xs font-medium uppercase text-gray-500">Separação</p>
                            <p class="mt-1 text-sm text-gray-700">{{ order.preparing_at ? formatDate(order.preparing_at) : 'Pendente' }}</p>
                        </li>
                        <li class="rounded-md border border-gray-200 p-3">
                            <p class="text-xs font-medium uppercase text-gray-500">Envio</p>
                            <p class="mt-1 text-sm text-gray-700">{{ order.shipped_at ? formatDate(order.shipped_at) : 'Pendente' }}</p>
                        </li>
                        <li class="rounded-md border border-gray-200 p-3">
                            <p class="text-xs font-medium uppercase text-gray-500">Entrega</p>
                            <p class="mt-1 text-sm text-gray-700">{{ order.delivered_at ? formatDate(order.delivered_at) : 'Pendente' }}</p>
                        </li>
                    </ol>

                    <form
                        v-if="nextFulfillmentStatus"
                        class="mt-6 border-t border-gray-200 pt-5"
                        @submit.prevent="advanceFulfillment"
                    >
                        <div v-if="nextFulfillmentStatus.value === 'shipped'" class="mb-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Código de rastreio</label>
                                <input
                                    v-model="fulfillmentForm.tracking_code"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <InputError class="mt-2" :message="fulfillmentForm.errors.tracking_code" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Link de rastreio</label>
                                <input
                                    v-model="fulfillmentForm.tracking_url"
                                    type="url"
                                    placeholder="https://..."
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <InputError class="mt-2" :message="fulfillmentForm.errors.tracking_url" />
                            </div>
                        </div>
                        <InputError class="mb-3" :message="fulfillmentForm.errors.fulfillment_status" />
                        <PrimaryButton type="submit" :disabled="fulfillmentForm.processing">
                            {{ nextActionLabel(nextFulfillmentStatus.value) }}
                        </PrimaryButton>
                    </form>

                    <div
                        v-else-if="order.status === 'pending_payment'"
                        class="mt-6 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800"
                    >
                        A operação será liberada após a confirmação do pagamento.
                    </div>

                    <form
                        v-if="order.fulfillment_status === 'shipped'"
                        class="mt-6 grid gap-4 border-t border-gray-200 pt-5 sm:grid-cols-2"
                        @submit.prevent="updateTracking"
                    >
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código de rastreio</label>
                            <input v-model="trackingForm.tracking_code" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <InputError class="mt-2" :message="trackingForm.errors.tracking_code" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Link de rastreio</label>
                            <input v-model="trackingForm.tracking_url" type="url" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <InputError class="mt-2" :message="trackingForm.errors.tracking_url" />
                        </div>
                        <div class="sm:col-span-2">
                            <PrimaryButton type="submit" :disabled="trackingForm.processing">Salvar rastreio</PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Itens</h3>
                    <ul class="mt-4 divide-y divide-gray-200">
                        <li v-for="item in order.items" :key="item.id" class="flex justify-between gap-4 py-4 text-sm">
                            <div>
                                <p class="font-medium text-gray-900">{{ item.product_name }}</p>
                                <p class="text-gray-500">{{ item.variant_name }} · {{ item.quantity }} un.</p>
                            </div>
                            <p class="font-medium text-gray-900">{{ formatMoneyFromCents(item.line_total_cents) }}</p>
                        </li>
                    </ul>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Cliente e entrega</h3>
                    <div class="mt-4 space-y-3 text-sm text-gray-600">
                        <div>
                            <p class="font-medium text-gray-900">{{ order.customer.name }}</p>
                            <p>{{ order.customer.email }}</p>
                        </div>
                        <div>
                            <p>{{ order.shipping_address.recipient_name }}</p>
                            <p>{{ order.shipping_address.summary }}</p>
                            <p>CEP {{ order.shipping_address.postal_code }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="h-fit space-y-6">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Resumo</h3>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ formatMoneyFromCents(order.subtotal_cents) }}</dd></div>
                        <div v-if="order.discount_cents > 0" class="flex justify-between"><dt>Desconto</dt><dd>-{{ formatMoneyFromCents(order.discount_cents) }}</dd></div>
                        <div class="flex justify-between"><dt>Frete</dt><dd>{{ formatMoneyFromCents(order.shipping_cents) }}</dd></div>
                        <div class="flex justify-between border-t pt-2 font-semibold"><dt>Total</dt><dd>{{ formatMoneyFromCents(order.total_cents) }}</dd></div>
                    </dl>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Pagamento</h3>
                    <div v-if="order.payment" class="mt-4 text-sm text-gray-600">
                        <p>Pix · <span class="font-medium text-gray-900">{{ order.payment.status_label }}</span></p>
                        <p v-if="order.payment.paid_at" class="mt-1 text-xs">Pago em {{ formatDate(order.payment.paid_at) }}</p>
                        <p v-if="order.payment.refunded_amount_cents > 0" class="mt-2 text-blue-700">
                            Reembolsado: {{ formatMoneyFromCents(order.payment.refunded_amount_cents) }}
                        </p>
                    </div>
                    <p v-else class="mt-4 text-sm text-gray-500">Nenhum pagamento registrado.</p>
                    <button v-if="canRefund" type="button" class="mt-5 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50" :disabled="refundForm.processing" @click="refund">
                        Reembolsar integralmente
                    </button>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Observação interna</h3>
                    <p class="mt-1 text-xs text-gray-500">Visível somente para administradores.</p>
                    <form class="mt-4" @submit.prevent="updateNotes">
                        <textarea v-model="notesForm.internal_notes" rows="5" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <InputError class="mt-2" :message="notesForm.errors.internal_notes" />
                        <PrimaryButton type="submit" class="mt-4" :disabled="notesForm.processing">Salvar observação</PrimaryButton>
                    </form>
                </div>
            </aside>
        </div>
    </AdminLayout>
</template>
