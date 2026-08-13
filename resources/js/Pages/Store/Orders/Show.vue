<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { OrderDetail } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    order: OrderDetail;
}>();

const paymentForm = useForm({});
const copyMessage = ref('');

const canGeneratePix = computed(() => {
    return (
        props.order.status === 'pending_payment' &&
        (!props.order.payment || props.order.payment.can_retry)
    );
});

const generatePix = () => {
    paymentForm.post(route('store.orders.payment.pix', props.order.id), {
        preserveScroll: true,
    });
};

const copyPix = async () => {
    const code = props.order.payment?.pix_qr_code;

    if (!code) {
        return;
    }

    try {
        await navigator.clipboard.writeText(code);
        copyMessage.value = 'Codigo Pix copiado.';
    } catch {
        copyMessage.value = 'Selecione e copie o codigo manualmente.';
    }
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

const statusClasses = (status: string) => {
    if (status === 'paid') {
        return 'bg-green-100 text-green-800';
    }

    if (['payment_failed', 'cancelled', 'charged_back'].includes(status)) {
        return 'bg-red-100 text-red-800';
    }

    if (['refunded', 'partially_refunded'].includes(status)) {
        return 'bg-blue-100 text-blue-800';
    }

    return 'bg-yellow-100 text-yellow-800';
};
</script>

<template>
    <Head :title="`Pedido ${order.number}`" />

    <StoreLayout>
        <FlashAlert />

        <div class="mb-8">
            <Link
                :href="route('store.orders.index')"
                class="text-sm text-[var(--store-muted)] hover:text-[var(--store-ink)]"
            >
                Voltar aos pedidos
            </Link>
            <h1 class="mt-4 font-serif text-5xl tracking-[-0.045em]">
                Pedido {{ order.number }}
            </h1>
            <p class="mt-2 text-[var(--store-muted)]">
                Realizado em {{ formatDate(order.placed_at) }}
            </p>
            <span
                class="mt-3 inline-flex rounded-full px-3 py-1 text-sm font-medium"
                :class="statusClasses(order.status)"
            >
                {{ order.status_label }}
            </span>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
            <section class="space-y-6">
                <div
                    v-if="['paid', 'partially_refunded'].includes(order.status)"
                    class="rounded-[1.5rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-serif text-xl font-semibold">
                                Acompanhamento da entrega
                            </h2>
                            <p class="mt-1 text-sm text-[var(--store-muted)]">
                                {{ order.fulfillment_status_label }}
                            </p>
                        </div>
                        <a
                            v-if="order.tracking_url"
                            :href="order.tracking_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm font-semibold text-[var(--store-cocoa)] underline underline-offset-4"
                        >
                            Rastrear entrega
                        </a>
                    </div>

                    <ol class="mt-5 grid gap-3 sm:grid-cols-3">
                        <li class="rounded-xl border p-3" :class="order.preparing_at ? 'border-green-200 bg-green-50' : 'border-[var(--store-line)]'">
                            <p class="text-sm font-medium">Em separação</p>
                            <p class="mt-1 text-xs text-[var(--store-muted)]">{{ order.preparing_at ? formatDate(order.preparing_at) : 'Próxima etapa' }}</p>
                        </li>
                        <li class="rounded-xl border p-3" :class="order.shipped_at ? 'border-green-200 bg-green-50' : 'border-[var(--store-line)]'">
                            <p class="text-sm font-medium">Enviado</p>
                            <p class="mt-1 text-xs text-[var(--store-muted)]">{{ order.shipped_at ? formatDate(order.shipped_at) : 'Pendente' }}</p>
                        </li>
                        <li class="rounded-xl border p-3" :class="order.delivered_at ? 'border-green-200 bg-green-50' : 'border-[var(--store-line)]'">
                            <p class="text-sm font-medium">Entregue</p>
                            <p class="mt-1 text-xs text-[var(--store-muted)]">{{ order.delivered_at ? formatDate(order.delivered_at) : 'Pendente' }}</p>
                        </li>
                    </ol>

                    <p v-if="order.tracking_code" class="mt-4 text-sm text-[var(--store-muted)]">
                        Código de rastreio:
                        <span class="font-medium text-[var(--store-ink)]">{{ order.tracking_code }}</span>
                    </p>
                </div>

                <div
                    v-else-if="order.fulfillment_status === 'cancelled'"
                    class="rounded-[1.5rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-5 text-sm text-[var(--store-muted)]"
                >
                    A operação de entrega deste pedido foi encerrada.
                </div>

                <div class="rounded-[1.5rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6">
                    <h2 class="font-serif text-xl font-semibold">Itens</h2>
                    <ul class="mt-4 divide-y divide-[var(--store-ink)]/10">
                        <li
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex flex-col gap-2 py-4 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <Link
                                    :href="route('store.products.show', item.product_slug)"
                                    class="font-medium hover:text-[var(--store-cocoa)]"
                                >
                                    {{ item.product_name }}
                                </Link>
                                <p class="text-sm text-[var(--store-muted)]">
                                    {{ item.variant_name }} · {{ item.variant_sku }}
                                </p>
                                <p class="text-sm text-[var(--store-muted)]">
                                    Qtd: {{ item.quantity }}
                                </p>
                                <Link
                                    v-if="order.fulfillment_status === 'delivered'"
                                    :href="`${route('store.products.show', item.product_slug)}#avaliacoes`"
                                    class="mt-2 inline-block text-xs font-semibold text-[var(--store-cocoa)] underline underline-offset-4"
                                >
                                    Avaliar compra verificada
                                </Link>
                            </div>
                            <div class="text-sm sm:text-right">
                                <p class="font-medium">
                                    {{ formatMoneyFromCents(item.line_total_cents) }}
                                </p>
                                <p class="text-[var(--store-muted)]">
                                    {{ formatMoneyFromCents(item.unit_price_cents) }} un.
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="rounded-[1.5rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6">
                    <h2 class="font-serif text-xl font-semibold">
                        Entrega
                    </h2>
                    <dl class="mt-4 space-y-2 text-sm text-[var(--store-muted)]">
                        <div>
                            <dt class="font-medium text-[var(--store-ink)]">
                                {{ order.shipping_method_name }}
                            </dt>
                        </div>
                        <div>
                            <dt>{{ order.shipping_address.recipient_name }}</dt>
                            <dd>
                                {{ order.shipping_address.summary }}
                            </dd>
                            <dd v-if="order.shipping_address.complement">
                                {{ order.shipping_address.complement }}
                            </dd>
                            <dd>
                                CEP {{ order.shipping_address.postal_code }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-[1.5rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6">
                    <h2 class="font-serif text-xl font-semibold">
                        Pagamento
                    </h2>

                    <div
                        v-if="
                            order.status === 'pending_payment' &&
                            (order.payment?.pix_qr_code ||
                                order.payment?.pix_ticket_url)
                        "
                        class="mt-4 space-y-4"
                    >
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <img
                                v-if="order.payment.pix_qr_code_base64"
                                :src="`data:image/png;base64,${order.payment.pix_qr_code_base64}`"
                                alt="QR Code para pagamento via Pix"
                                class="h-48 w-48 rounded-xl border border-[var(--store-line)] bg-[var(--store-paper)] p-2"
                            />

                            <div class="min-w-0 flex-1">
                                <p class="font-medium">
                                    Pague com Pix
                                </p>
                                <p class="mt-1 text-sm text-[var(--store-muted)]">
                                    Escaneie o QR Code ou use o codigo Copia e Cola no aplicativo do seu banco.
                                </p>

                                <textarea
                                    v-if="order.payment.pix_qr_code"
                                    :value="order.payment.pix_qr_code"
                                    readonly
                                    rows="3"
                                    class="mt-3 block w-full rounded-xl border-[var(--store-line)] bg-[var(--store-canvas)] text-xs text-[var(--store-ink)]"
                                    aria-label="Codigo Pix Copia e Cola"
                                />

                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <button
                                        v-if="order.payment.pix_qr_code"
                                        type="button"
                                        class="rounded-full bg-[var(--store-cocoa)] px-4 py-2 text-sm font-semibold text-[var(--store-cream)] hover:bg-[var(--store-ink)]"
                                        @click="copyPix"
                                    >
                                        Copiar codigo Pix
                                    </button>
                                    <a
                                        v-if="order.payment.pix_ticket_url"
                                        :href="order.payment.pix_ticket_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-sm font-semibold text-[var(--store-cocoa)] underline underline-offset-4"
                                    >
                                        Abrir pagina de pagamento
                                    </a>
                                </div>
                                <p
                                    v-if="copyMessage"
                                    class="mt-2 text-sm text-[var(--store-muted)]"
                                    role="status"
                                >
                                    {{ copyMessage }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="order.payment?.status === 'processing'"
                        class="mt-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800"
                    >
                        O Mercado Pago esta processando a solicitacao do Pix. Atualize a pagina em instantes.
                    </div>

                    <div
                        v-else-if="order.status === 'paid'"
                        class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800"
                    >
                        Pagamento confirmado.
                    </div>

                    <div
                        v-else-if="['refunded', 'partially_refunded'].includes(order.status)"
                        class="mt-4 rounded-md bg-blue-50 p-4 text-sm text-blue-800"
                    >
                        {{ order.status_label }}.
                    </div>

                    <div
                        v-else-if="['payment_failed', 'cancelled', 'charged_back'].includes(order.status)"
                        class="mt-4 rounded-md bg-red-50 p-4 text-sm text-red-800"
                    >
                        {{ order.status_label }}. Este pedido nao aceita uma nova cobranca.
                    </div>

                    <div v-else-if="canGeneratePix" class="mt-4">
                        <p class="text-sm text-[var(--store-muted)]">
                            Gere o QR Code para pagar este pedido via Pix.
                        </p>
                        <button
                            type="button"
                            class="mt-3 rounded-full bg-[var(--store-cocoa)] px-4 py-2 text-sm font-semibold text-[var(--store-cream)] hover:bg-[var(--store-ink)] disabled:opacity-50"
                            :disabled="paymentForm.processing"
                            @click="generatePix"
                        >
                            Gerar Pix
                        </button>
                    </div>
                </div>
            </section>

            <aside class="h-fit rounded-[1.5rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6">
                <h2 class="font-serif text-xl font-semibold">Resumo</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-[var(--store-muted)]">Subtotal</dt>
                        <dd>{{ formatMoneyFromCents(order.subtotal_cents) }}</dd>
                    </div>
                    <div
                        v-if="order.discount_cents > 0"
                        class="flex justify-between text-green-700"
                    >
                        <dt>
                            Desconto
                            <span v-if="order.coupon">({{ order.coupon.code }})</span>
                        </dt>
                        <dd>-{{ formatMoneyFromCents(order.discount_cents) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-[var(--store-muted)]">Frete</dt>
                        <dd>
                            {{
                                order.shipping_cents === 0
                                    ? 'Gratis'
                                    : formatMoneyFromCents(order.shipping_cents)
                            }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t border-[var(--store-line)] pt-2 font-semibold"
                    >
                        <dt>Total</dt>
                        <dd>{{ formatMoneyFromCents(order.total_cents) }}</dd>
                    </div>
                </dl>

                <div
                    v-if="order.payment"
                    class="mt-4 rounded-xl bg-[var(--store-canvas)] p-4 text-sm text-[var(--store-ink)]"
                >
                    <p class="font-medium">Pix · {{ order.payment.status_label }}</p>
                    <p v-if="order.payment.expires_at" class="mt-1 text-xs text-[var(--store-muted)]">
                        Valido ate {{ formatDate(order.payment.expires_at) }}
                    </p>
                </div>
            </aside>
        </div>
    </StoreLayout>
</template>
