<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
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
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-cocoa)]">
                    Sua conta
                </p>
                <h1 class="mt-2 font-serif text-5xl tracking-[-0.045em]">Meus enderecos</h1>
                <p class="mt-2 text-[var(--store-muted)]">
                    Gerencie os enderecos usados no checkout.
                </p>
            </div>
            <Link
                :href="route('store.addresses.create')"
                class="rounded-full bg-[var(--store-ink)] px-5 py-3 text-sm font-bold text-[var(--store-cream)]"
            >
                Novo endereco
            </Link>
        </div>

        <div v-if="addresses.length > 0" class="grid gap-4 md:grid-cols-2">
            <article
                v-for="address in addresses"
                :key="address.id"
                class="rounded-[1.5rem] border border-[var(--store-ink)]/12 bg-[var(--store-paper)] p-6"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-serif text-xl font-semibold">
                                {{ address.label }}
                            </h2>
                            <span
                                v-if="address.is_default"
                                class="rounded-full bg-[var(--store-accent)] px-2.5 py-1 text-xs font-bold text-[var(--store-cocoa)]"
                            >
                                Padrao
                            </span>
                        </div>
                        <p class="mt-2 text-sm">
                            {{ address.recipient_name }}
                        </p>
                        <p class="text-sm text-[var(--store-muted)]">
                            {{ address.summary }}
                        </p>
                        <p class="text-sm text-[var(--store-muted)]">
                            CEP {{ address.formatted_postal_code }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 text-sm">
                        <Link
                            :href="route('store.addresses.edit', address.id)"
                            class="font-semibold text-[var(--store-cocoa)] underline underline-offset-4"
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
            class="rounded-[2rem] border border-dashed border-[var(--store-ink)]/25 bg-[var(--store-paper)] p-12 text-center"
        >
            <p class="font-serif text-2xl font-medium">
                Nenhum endereco cadastrado
            </p>
            <p class="mt-2 text-sm text-[var(--store-muted)]">
                Cadastre um endereco para continuar a compra.
            </p>
            <Link
                :href="route('store.addresses.create')"
                class="mt-6 inline-block rounded-full bg-[var(--store-ink)] px-5 py-3 text-sm font-bold text-[var(--store-cream)]"
            >
                Cadastrar endereco
            </Link>
        </div>
    </StoreLayout>
</template>
