<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    states: string[];
}>();

const form = useForm({
    label: 'Casa',
    recipient_name: '',
    recipient_phone: '',
    postal_code: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: 'SP',
    is_default: true,
});

const submit = () => {
    form.post(route('store.addresses.store'));
};
</script>

<template>
    <Head title="Novo endereco" />

    <StoreLayout>
        <FlashAlert />

        <div class="mx-auto max-w-2xl">
            <h1 class="text-3xl font-bold text-gray-900">Novo endereco</h1>

            <form
                class="mt-8 space-y-4 rounded-lg border border-gray-200 bg-white p-6"
                @submit.prevent="submit"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="label" value="Identificacao" />
                        <TextInput
                            id="label"
                            v-model="form.label"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.label" />
                    </div>
                    <div>
                        <InputLabel for="recipient_name" value="Destinatario" />
                        <TextInput
                            id="recipient_name"
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
                        <InputLabel for="postal_code" value="CEP" />
                        <TextInput
                            id="postal_code"
                            v-model="form.postal_code"
                            placeholder="00000-000"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.postal_code"
                        />
                    </div>
                    <div>
                        <InputLabel for="recipient_phone" value="Telefone" />
                        <TextInput
                            id="recipient_phone"
                            v-model="form.recipient_phone"
                            class="mt-1 block w-full"
                        />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-[2fr_1fr]">
                    <div>
                        <InputLabel for="street" value="Rua" />
                        <TextInput
                            id="street"
                            v-model="form.street"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.street" />
                    </div>
                    <div>
                        <InputLabel for="number" value="Numero" />
                        <TextInput
                            id="number"
                            v-model="form.number"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.number" />
                    </div>
                </div>

                <div>
                    <InputLabel for="complement" value="Complemento" />
                    <TextInput
                        id="complement"
                        v-model="form.complement"
                        class="mt-1 block w-full"
                    />
                </div>

                <div>
                    <InputLabel for="neighborhood" value="Bairro" />
                    <TextInput
                        id="neighborhood"
                        v-model="form.neighborhood"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.neighborhood"
                    />
                </div>

                <div class="grid gap-4 md:grid-cols-[2fr_1fr]">
                    <div>
                        <InputLabel for="city" value="Cidade" />
                        <TextInput
                            id="city"
                            v-model="form.city"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.city" />
                    </div>
                    <div>
                        <InputLabel for="state" value="UF" />
                        <select
                            id="state"
                            v-model="form.state"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                        <InputError class="mt-2" :message="form.errors.state" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_default" />
                    <span class="text-sm text-gray-700">Endereco padrao</span>
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton :disabled="form.processing">
                        Salvar
                    </PrimaryButton>
                    <Link
                        :href="route('store.addresses.index')"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </StoreLayout>
</template>
