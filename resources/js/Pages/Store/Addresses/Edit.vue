<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { CustomerAddress } from '@/types/catalog';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    address: CustomerAddress;
    states: string[];
}>();

const form = useForm({
    label: props.address.label,
    recipient_name: props.address.recipient_name,
    recipient_phone: props.address.recipient_phone ?? '',
    postal_code: props.address.formatted_postal_code,
    street: props.address.street,
    number: props.address.number,
    complement: props.address.complement ?? '',
    neighborhood: props.address.neighborhood,
    city: props.address.city,
    state: props.address.state,
    is_default: props.address.is_default,
});

const submit = () => {
    form.put(route('store.addresses.update', props.address.id));
};
</script>

<template>
    <Head title="Editar endereco" />

    <StoreLayout>
        <FlashAlert />

        <div class="mx-auto max-w-2xl">
            <p class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-cocoa)]">Entrega</p>
            <h1 class="mt-2 font-serif text-5xl tracking-[-0.045em]">Editar endereco</h1>

            <form
                class="store-form mt-8 space-y-5 rounded-[2rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6 sm:p-8"
                @submit.prevent="submit"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Identificacao" />
                        <TextInput
                            v-model="form.label"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.label" />
                    </div>
                    <div>
                        <InputLabel value="Destinatario" />
                        <TextInput
                            v-model="form.recipient_name"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.recipient_name"
                        />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="CEP" />
                        <TextInput
                            v-model="form.postal_code"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.postal_code"
                        />
                    </div>
                    <div>
                        <InputLabel value="Telefone" />
                        <TextInput
                            v-model="form.recipient_phone"
                            class="mt-1 block w-full"
                        />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-[2fr_1fr]">
                    <div>
                        <InputLabel value="Rua" />
                        <TextInput
                            v-model="form.street"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>
                    <div>
                        <InputLabel value="Numero" />
                        <TextInput
                            v-model="form.number"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>
                </div>

                <div>
                    <InputLabel value="Complemento" />
                    <TextInput
                        v-model="form.complement"
                        class="mt-1 block w-full"
                    />
                </div>

                <div>
                    <InputLabel value="Bairro" />
                    <TextInput
                        v-model="form.neighborhood"
                        class="mt-1 block w-full"
                        required
                    />
                </div>

                <div class="grid gap-4 md:grid-cols-[2fr_1fr]">
                    <div>
                        <InputLabel value="Cidade" />
                        <TextInput
                            v-model="form.city"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>
                    <div>
                        <InputLabel value="UF" />
                        <select
                            v-model="form.state"
                            class="mt-1 block w-full rounded-xl border-[var(--store-line)] bg-[var(--store-paper)] shadow-none focus:border-[var(--store-cocoa)] focus:ring-[var(--store-cocoa)]/20"
                            required
                        >
                            <option
                                v-for="state in states"
                                :key="state"
                                :value="state"
                            >
                                {{ state }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_default" />
                    <span class="text-sm text-[var(--store-muted)]">Endereco padrao</span>
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton :disabled="form.processing">
                        Salvar
                    </PrimaryButton>
                    <Link
                        :href="route('store.addresses.index')"
                        class="text-sm text-[var(--store-muted)] hover:text-[var(--store-ink)]"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </StoreLayout>
</template>
