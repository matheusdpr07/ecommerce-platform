<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { CheckoutPayload } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<CheckoutPayload>();

const form = useForm({
    shipping_address_id:
        props.selected_address_id ??
        props.addresses.find((address) => address.is_default)?.id ??
        null,
    shipping_method_id: props.selected_shipping_method_id,
});
const confirmForm = useForm({});

const submit = () => {
    form.patch(route('store.checkout.update'), { preserveScroll: true });
};
const confirmOrder = () => {
    confirmForm.post(route('store.checkout.store'), { preserveScroll: true });
};
const confirmError = computed(() => {
    const errors = confirmForm.errors as Record<string, string | undefined>;
    return errors.checkout || errors.cart || '';
});
const deliveryEstimate = (min?: number | null, max?: number | null) => {
    if (min && max) return `${min} a ${max} dias úteis`;
    if (min) return `A partir de ${min} dias úteis`;
    return null;
};
</script>

<template>
    <Head title="Finalizar compra" />

    <StoreLayout>
        <div class="mb-10 border-b border-[var(--store-ink)]/15 pb-8">
            <Link
                :href="route('store.cart.index')"
                view-transition
                class="text-xs font-bold text-[var(--store-muted)] underline underline-offset-4"
            >
                ← Voltar ao carrinho
            </Link>
            <div
                class="mt-5 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <p
                        class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-coral)]"
                    >
                        Última etapa
                    </p>
                    <h1
                        class="mt-2 font-serif text-5xl leading-none tracking-[-0.06em] sm:text-7xl"
                    >
                        Entrega e revisão
                    </h1>
                </div>
                <ol class="flex items-center gap-2 text-[0.62rem] font-bold uppercase tracking-[0.12em]">
                    <li class="flex items-center gap-2 opacity-45">
                        <span class="grid size-7 place-items-center rounded-full border">1</span>
                        Carrinho
                    </li>
                    <li class="h-px w-5 bg-[var(--store-line)]" />
                    <li class="flex items-center gap-2">
                        <span class="grid size-7 place-items-center rounded-full bg-[var(--store-ink)] text-white">2</span>
                        Entrega
                    </li>
                    <li class="h-px w-5 bg-[var(--store-line)]" />
                    <li class="flex items-center gap-2" :class="is_ready ? '' : 'opacity-35'">
                        <span class="grid size-7 place-items-center rounded-full border">3</span>
                        Pagamento
                    </li>
                </ol>
            </div>
        </div>

        <div class="grid gap-12 lg:grid-cols-[1fr_24rem] xl:gap-20">
            <form class="space-y-8" @submit.prevent="submit">
                <section>
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <span
                                class="text-[0.62rem] font-bold uppercase tracking-[0.18em] text-[var(--store-muted)]"
                            >
                                Passo 01
                            </span>
                            <h2 class="mt-1 font-serif text-3xl tracking-[-0.035em]">
                                Onde devemos entregar?
                            </h2>
                        </div>
                        <Link
                            :href="route('store.addresses.index')"
                            class="text-xs font-bold underline underline-offset-4"
                        >
                            Gerenciar endereços
                        </Link>
                    </div>

                    <div
                        v-if="addresses.length === 0"
                        class="mt-5 rounded-[1.5rem] bg-amber-50 p-5 text-sm text-amber-900"
                    >
                        Falta cadastrar o endereço de entrega.
                        <Link
                            :href="route('store.addresses.create')"
                            class="ml-1 font-bold underline"
                        >
                            Cadastrar agora
                        </Link>
                    </div>
                    <div v-else class="mt-5 grid gap-3 sm:grid-cols-2">
                        <label
                            v-for="address in addresses"
                            :key="address.id"
                            class="relative cursor-pointer rounded-[1.5rem] border p-5 transition"
                            :class="
                                form.shipping_address_id === address.id
                                    ? 'border-[var(--store-ink)] bg-[var(--store-paper)] shadow-md'
                                    : 'border-[var(--store-ink)]/12 hover:border-[var(--store-ink)]/40'
                            "
                        >
                            <input
                                v-model="form.shipping_address_id"
                                type="radio"
                                class="absolute right-5 top-5 text-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                                :value="address.id"
                            />
                            <span class="font-serif text-xl">{{ address.label }}</span>
                            <span
                                v-if="address.is_default"
                                class="ml-2 rounded-full bg-[var(--store-accent)] px-2 py-1 text-[0.55rem] font-bold uppercase tracking-wider"
                            >
                                Padrão
                            </span>
                            <span class="mt-3 block text-sm font-semibold">
                                {{ address.recipient_name }}
                            </span>
                            <span class="mt-1 block pr-6 text-sm leading-6 text-[var(--store-muted)]">
                                {{ address.summary }}
                            </span>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.shipping_address_id" />
                </section>

                <section class="border-t border-[var(--store-ink)]/15 pt-8">
                    <span
                        class="text-[0.62rem] font-bold uppercase tracking-[0.18em] text-[var(--store-muted)]"
                    >
                        Passo 02
                    </span>
                    <h2 class="mt-1 font-serif text-3xl tracking-[-0.035em]">
                        Como prefere receber?
                    </h2>

                    <p
                        v-if="!form.shipping_address_id"
                        class="mt-5 text-sm text-[var(--store-muted)]"
                    >
                        Escolha um endereço para liberar as opções de entrega.
                    </p>
                    <div
                        v-else-if="shipping_methods.length === 0"
                        class="mt-5 rounded-[1.5rem] bg-amber-50 p-5 text-sm text-amber-900"
                    >
                        Nenhuma opção de frete está disponível para este pedido.
                    </div>
                    <div v-else class="mt-5 space-y-3">
                        <label
                            v-for="method in shipping_methods"
                            :key="method.id"
                            class="flex cursor-pointer items-start gap-4 rounded-[1.5rem] border p-5 transition"
                            :class="
                                form.shipping_method_id === method.id
                                    ? 'border-[var(--store-ink)] bg-[var(--store-paper)] shadow-md'
                                    : 'border-[var(--store-ink)]/12 hover:border-[var(--store-ink)]/40'
                            "
                        >
                            <input
                                v-model="form.shipping_method_id"
                                type="radio"
                                class="mt-1 text-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                                :value="method.id"
                            />
                            <span class="min-w-0 flex-1">
                                <span class="font-serif text-xl">{{ method.name }}</span>
                                <span
                                    v-if="method.description"
                                    class="mt-1 block text-sm text-[var(--store-muted)]"
                                >
                                    {{ method.description }}
                                </span>
                                <span
                                    v-if="deliveryEstimate(method.estimated_days_min, method.estimated_days_max)"
                                    class="mt-2 block text-xs font-semibold"
                                >
                                    {{ deliveryEstimate(method.estimated_days_min, method.estimated_days_max) }}
                                </span>
                            </span>
                            <span class="font-bold">
                                {{ method.price_cents === 0 ? 'Grátis' : formatMoneyFromCents(method.price_cents) }}
                            </span>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.shipping_method_id" />
                </section>

                <button
                    type="submit"
                    class="flex w-full items-center justify-between rounded-full border border-[var(--store-ink)] px-6 py-4 text-sm font-bold transition hover:bg-[var(--store-ink)] hover:text-white disabled:opacity-50 sm:w-auto sm:min-w-72"
                    :disabled="form.processing || !form.shipping_address_id"
                >
                    Atualizar entrega e total
                    <span aria-hidden="true">↻</span>
                </button>
            </form>

            <aside class="h-fit lg:sticky lg:top-28">
                <div
                    class="rounded-[2rem] bg-[var(--store-ink)] p-7 text-[var(--store-paper)] shadow-[0_25px_60px_rgba(23,24,17,0.18)]"
                >
                    <p
                        class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-[var(--store-accent)]"
                    >
                        Revisão final
                    </p>
                    <dl class="mt-6 space-y-3 text-sm">
                        <div class="flex justify-between text-white/65">
                            <dt>{{ cart.item_count }} item(ns)</dt>
                            <dd>{{ formatMoneyFromCents(cart.subtotal_cents) }}</dd>
                        </div>
                        <div
                            v-if="cart.discount_cents > 0"
                            class="flex justify-between text-[var(--store-accent)]"
                        >
                            <dt>Desconto</dt>
                            <dd>-{{ formatMoneyFromCents(cart.discount_cents) }}</dd>
                        </div>
                        <div class="flex justify-between text-white/65">
                            <dt>Entrega</dt>
                            <dd>
                                {{ shipping_cents === 0 && is_ready ? 'Grátis' : formatMoneyFromCents(shipping_cents) }}
                            </dd>
                        </div>
                        <div
                            class="mt-5 flex items-end justify-between border-t border-white/15 pt-5"
                        >
                            <dt class="font-bold">Total</dt>
                            <dd class="font-serif text-3xl tracking-[-0.04em]">
                                {{ formatMoneyFromCents(grand_total_cents) }}
                            </dd>
                        </div>
                    </dl>

                    <div v-if="is_ready" class="mt-7">
                        <div
                            class="mb-4 rounded-2xl bg-white/8 p-4 text-xs leading-5 text-white/65"
                        >
                            Tudo certo. Ao confirmar, criaremos o pedido e você
                            seguirá para o pagamento seguro via Pix.
                        </div>
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-full bg-[var(--store-accent)] px-6 py-4 text-sm font-bold text-[var(--store-ink)] transition hover:-translate-y-0.5 disabled:opacity-50"
                            :disabled="confirmForm.processing"
                            @click="confirmOrder"
                        >
                            Confirmar e ir para pagamento
                            <span aria-hidden="true">→</span>
                        </button>
                        <InputError class="mt-3" :message="confirmError" />
                    </div>
                    <div
                        v-else
                        class="mt-7 rounded-2xl border border-white/15 p-4 text-xs leading-5 text-white/55"
                    >
                        Selecione endereço e frete e atualize o total para liberar a confirmação.
                    </div>
                </div>
                <p class="mt-4 text-center text-xs text-[var(--store-muted)]">
                    ✓ Pagamento protegido · seus dados permanecem seguros
                </p>
            </aside>
        </div>
    </StoreLayout>
</template>
