<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
    checkoutIntent?: boolean;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verificar e-mail" />

        <div class="mb-4">
            <h1 class="text-xl font-semibold text-gray-900">
                Verifique seu e-mail
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                Enviamos um link de confirmacao para o seu e-mail. Abra esse
                link para ativar a conta.
            </p>
            <p
                v-if="checkoutIntent"
                class="mt-3 rounded-md bg-indigo-50 p-3 text-sm text-indigo-800"
            >
                Seu carrinho esta salvo e a compra continuara no checkout assim
                que o e-mail for confirmado.
            </p>
        </div>

        <div
            class="mb-4 text-sm font-medium text-green-600"
            v-if="verificationLinkSent"
        >
            Um novo link de verificacao foi enviado para o seu e-mail.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Reenviar verificacao
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >Sair</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
