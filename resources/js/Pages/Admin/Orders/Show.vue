<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { AdminOrderDetail } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    order: AdminOrderDetail;
    canRefund: boolean;
}>();

const refundForm = useForm({});

const refund = () => {
    if (!window.confirm('Confirma o reembolso integral deste pedido?')) {
        return;
    }

    refundForm.post(route('admin.orders.refund', props.order.id), {
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
            </div>
        </template>

        <FlashAlert />

        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <section class="space-y-6">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Itens</h3>
                    <ul class="mt-4 divide-y divide-gray-200">
                        <li
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex justify-between gap-4 py-4 text-sm"
                        >
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ item.product_name }}
                                </p>
                                <p class="text-gray-500">
                                    {{ item.variant_name }} · {{ item.quantity }} un.
                                </p>
                            </div>
                            <p class="font-medium text-gray-900">
                                {{ formatMoneyFromCents(item.line_total_cents) }}
                            </p>
                        </li>
                    </ul>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Cliente e entrega
                    </h3>
                    <div class="mt-4 space-y-3 text-sm text-gray-600">
                        <div>
                            <p class="font-medium text-gray-900">
                                {{ order.customer.name }}
                            </p>
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
                        <div class="flex justify-between">
                            <dt>Subtotal</dt>
                            <dd>{{ formatMoneyFromCents(order.subtotal_cents) }}</dd>
                        </div>
                        <div v-if="order.discount_cents > 0" class="flex justify-between">
                            <dt>Desconto</dt>
                            <dd>-{{ formatMoneyFromCents(order.discount_cents) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Frete</dt>
                            <dd>{{ formatMoneyFromCents(order.shipping_cents) }}</dd>
                        </div>
                        <div class="flex justify-between border-t pt-2 font-semibold">
                            <dt>Total</dt>
                            <dd>{{ formatMoneyFromCents(order.total_cents) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Pagamento</h3>
                    <div v-if="order.payment" class="mt-4 text-sm text-gray-600">
                        <p>
                            Pix ·
                            <span class="font-medium text-gray-900">
                                {{ order.payment.status_label }}
                            </span>
                        </p>
                        <p v-if="order.payment.paid_at" class="mt-1 text-xs">
                            Pago em {{ formatDate(order.payment.paid_at) }}
                        </p>
                    </div>
                    <p v-else class="mt-4 text-sm text-gray-500">
                        Nenhum pagamento registrado.
                    </p>

                    <button
                        v-if="canRefund"
                        type="button"
                        class="mt-5 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                        :disabled="refundForm.processing"
                        @click="refund"
                    >
                        Reembolsar integralmente
                    </button>
                </div>
            </aside>
        </div>
    </AdminLayout>
</template>
