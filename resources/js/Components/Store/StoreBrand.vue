<script setup lang="ts">
defineProps<{
    name: string;
    eyebrow?: string;
    inverse?: boolean;
}>();
</script>

<template>
    <span class="inline-flex items-center gap-3">
        <span
            class="grid size-10 shrink-0 place-items-center rounded-full border"
            :class="
                inverse
                    ? 'border-white/25 bg-white text-[var(--store-ink)]'
                    : 'border-[var(--store-ink)] bg-[var(--store-accent)] text-[var(--store-ink)]'
            "
            aria-hidden="true"
        >
            <svg viewBox="0 0 32 32" class="size-5" fill="none">
                <path
                    d="M16 3.5c.8 6.7 5.8 11.7 12.5 12.5C21.8 16.8 16.8 21.8 16 28.5 15.2 21.8 10.2 16.8 3.5 16 10.2 15.2 15.2 10.2 16 3.5Z"
                    fill="currentColor"
                />
            </svg>
        </span>

        <span class="leading-none">
            <span
                class="block font-serif text-2xl font-semibold tracking-[-0.04em]"
            >
                {{ name }}
            </span>
            <span
                v-if="eyebrow"
                class="mt-1 block text-[0.58rem] font-semibold uppercase tracking-[0.24em] opacity-65"
            >
                {{ eyebrow }}
            </span>
        </span>
    </span>
</template>
