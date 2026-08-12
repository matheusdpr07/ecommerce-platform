<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { CustomerAddress } from '@/types/catalog';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{
    addresses: CustomerAddress[];
}>();

const destroyAddress = (addressId: number) => {
    router.delete(route('store.addresses.destroy', addressId));
};
</script>

<template>
    <Head title="Meus enderecos" />

    <StoreLayout>
        <FlashAlert />

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Meus enderecos</h1>
                <p class="mt-2 text-gray-600">
                    Gerencie os enderecos usados no checkout.
                </p>
            </div>
            <Link :href="route('store.addresses.create')">
                <PrimaryButton>Novo endereco</PrimaryButton>
            </Link>
        </div>

        <div v-if="addresses.length > 0" class="grid gap-4 md:grid-cols-2">
            <article
                v-for="address in addresses"
                :key="address.id"
                class="rounded-lg border border-gray-200 bg-white p-5"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-semibold text-gray-900">
                                {{ address.label }}
                            </h2>
                            <span
                                v-if="address.is_default"
                                class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700"
                            >
                                Padrao
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-gray-700">
                            {{ address.recipient_name }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ address.summary }}
                        </p>
                        <p class="text-sm text-gray-600">
                            CEP {{ address.formatted_postal_code }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 text-sm">
                        <Link
                            :href="route('store.addresses.edit', address.id)"
                            class="text-indigo-600 hover:text-indigo-800"
                        >
                            Editar
                        </Link>
                        <button
                            type="button"
                            class="text-left text-red-600 hover:text-red-800"
                            @click="destroyAddress(address.id)"
                        >
                            Excluir
                        </button>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-else
            class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center"
        >
            <p class="text-lg font-medium text-gray-900">
                Nenhum endereco cadastrado
            </p>
            <p class="mt-2 text-sm text-gray-500">
                Cadastre um endereco para continuar a compra.
            </p>
            <Link :href="route('store.addresses.create')" class="mt-6 inline-block">
                <PrimaryButton>Cadastrar endereco</PrimaryButton>
            </Link>
        </div>
    </StoreLayout>
</template>
