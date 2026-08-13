<script setup lang="ts">
import StoreBrand from '@/Components/Store/StoreBrand.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div
        class="store-grain relative isolate flex min-h-screen flex-col items-center overflow-hidden bg-[var(--store-canvas)] px-4 pt-8 text-[var(--store-ink)] sm:justify-center sm:pt-0"
    >
        <span
            class="absolute -right-40 -top-48 -z-10 size-[34rem] rounded-full bg-[var(--store-caramel)]/20 blur-3xl"
            aria-hidden="true"
        />
        <span
            class="absolute -bottom-52 -left-40 -z-10 size-[32rem] rounded-full bg-[var(--store-cocoa)]/15 blur-3xl"
            aria-hidden="true"
        />

        <div class="relative z-10">
            <Link href="/" aria-label="Voltar para a loja">
                <StoreBrand
                    :name="page.props.store.name"
                    :eyebrow="page.props.store.eyebrow"
                />
            </Link>
        </div>

        <div
            class="store-auth store-form relative z-10 mt-7 w-full overflow-hidden rounded-[2rem] border border-[var(--store-ink)]/10 bg-[var(--store-paper)] px-6 py-7 shadow-[0_30px_80px_rgba(56,36,13,0.16)] sm:max-w-md sm:px-8 sm:py-8"
        >
            <slot />
        </div>
    </div>
</template>
