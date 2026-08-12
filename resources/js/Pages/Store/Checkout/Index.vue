<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { CheckoutPayload } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<CheckoutPayload>();

const form = useForm({
    shipping_address_id: props.selected_address_id ?? props.addresses.find((address) => address.is_default)?.id ?? null,
    shipping_method_id: props.selected_shipping_method_id,
});

const submit = () => {
    form.patch(route('store.checkout.update'), {
        preserveScroll: true,
    });
};

const deliveryEstimate = (min?: number | null, max?: number | null) => {
    if (min && max) {
        return `${min} a ${max} dias uteis`;
    }

    if (min) {
        return `A partir de ${min} dias uteis`;
    }

    return null;
};
</script>

<template>
    <Head title="Checkout" />

    <StoreLayout>
        <FlashAlert />

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Checkout</h1>
            <p class="mt-2 text-gray-600">
                Selecione endereco e frete para revisar o total da compra.
            </p>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Endereco de entrega
                        </h2>
                        <Link
                            :href="route('store.addresses.index')"
                            class="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Gerenciar enderecos
                        </Link>
                    </div>

                    <div
                        v-if="addresses.length === 0"
                        class="mt-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800"
                    >
                        Cadastre um endereco antes de continuar.
                        <Link
                            :href="route('store.addresses.create')"
                            class="ml-1 font-medium underline"
                        >
                            Cadastrar endereco
                        </Link>
                    </div>

                    <div v-else class="mt-4 space-y-3">
                        <label
                            v-for="address in addresses"
                            :key="address.id"
                            class="flex cursor-pointer gap-3 rounded-md border p-4"
                            :class="
                                form.shipping_address_id === address.id
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-gray-200'
                            "
                        >
                            <input
                                v-model="form.shipping_address_id"
                                type="radio"
                                class="mt-1 text-indigo-600 focus:ring-indigo-500"
                                :value="address.id"
                            />
                            <span>
                                <span class="font-medium text-gray-900">
                                    {{ address.label }}
                                </span>
                                <span
                                    v-if="address.is_default"
                                    class="ml-2 text-xs text-indigo-700"
                                >
                                    Padrao
                                </span>
                                <span class="mt-1 block text-sm text-gray-600">
                                    {{ address.recipient_name }} ·
                                    {{ address.summary }}
                                </span>
                            </span>
                        </label>
                    </div>
                    <InputError
                        class="mt-2"
                        :message="form.errors.shipping_address_id"
                    />
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Frete
                    </h2>

                    <div
                        v-if="!form.shipping_address_id"
                        class="mt-4 text-sm text-gray-500"
                    >
                        Selecione um endereco para ver as opcoes de frete.
                    </div>

                    <div
                        v-else-if="shipping_methods.length === 0"
                        class="mt-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800"
                    >
                        Nenhuma opcao de frete disponivel para este pedido.
                    </div>

                    <div v-else class="mt-4 space-y-3">
                        <label
                            v-for="method in shipping_methods"
                            :key="method.id"
                            class="flex cursor-pointer items-start justify-between gap-4 rounded-md border p-4"
                            :class="
                                form.shipping_method_id === method.id
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-gray-200'
                            "
                        >
                            <span class="flex gap-3">
                                <input
                                    v-model="form.shipping_method_id"
                                    type="radio"
                                    class="mt-1 text-indigo-600 focus:ring-indigo-500"
                                    :value="method.id"
                                />
                                <span>
                                    <span class="font-medium text-gray-900">
                                        {{ method.name }}
                                    </span>
                                    <span
                                        v-if="method.description"
                                        class="mt-1 block text-sm text-gray-600"
                                    >
                                        {{ method.description }}
                                    </span>
                                    <span
                                        v-if="
                                            deliveryEstimate(
                                                method.estimated_days_min,
                                                method.estimated_days_max,
                                            )
                                        "
                                        class="mt-1 block text-xs text-gray-500"
                                    >
                                        {{
                                            deliveryEstimate(
                                                method.estimated_days_min,
                                                method.estimated_days_max,
                                            )
                                        }}
                                    </span>
                                </span>
                            </span>
                            <span class="font-semibold text-gray-900">
                                {{
                                    method.price_cents === 0
                                        ? 'Gratis'
                                        : formatMoneyFromCents(
                                              method.price_cents,
                                          )
                                }}
                            </span>
                        </label>
                    </div>
                    <InputError
                        class="mt-2"
                        :message="form.errors.shipping_method_id"
                    />
                </section>

                <PrimaryButton type="submit" :disabled="form.processing">
                    Salvar selecoes
                </PrimaryButton>
            </form>

            <aside class="h-fit rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-gray-900">Resumo</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Subtotal</dt>
                        <dd>{{ formatMoneyFromCents(cart.subtotal_cents) }}</dd>
                    </div>
                    <div
                        v-if="cart.discount_cents > 0"
                        class="flex justify-between text-green-700"
                    >
                        <dt>Desconto</dt>
                        <dd>-{{ formatMoneyFromCents(cart.discount_cents) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-600">Frete</dt>
                        <dd>
                            {{
                                shipping_cents === 0 && is_ready
                                    ? 'Gratis'
                                    : formatMoneyFromCents(shipping_cents)
                            }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between border-t border-gray-200 pt-2 font-semibold"
                    >
                        <dt>Total</dt>
                        <dd>{{ formatMoneyFromCents(grand_total_cents) }}</dd>
                    </div>
                </dl>

                <div
                    v-if="is_ready"
                    class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800"
                >
                    Endereco e frete selecionados. A confirmacao do pedido sera
                    habilitada na proxima fase.
                </div>

                <Link
                    :href="route('store.cart.index')"
                    class="mt-4 inline-block text-sm text-gray-600 hover:text-gray-900"
                >
                    Voltar ao carrinho
                </Link>
            </aside>
        </div>
    </StoreLayout>
</template>
