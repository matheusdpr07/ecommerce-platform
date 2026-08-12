<script setup lang="ts">
import type { Paginated } from '@/types/catalog';
import { Link } from '@inertiajs/vue3';

withDefaults(defineProps<{
    pagination: Paginated<unknown>;
    theme?: 'default' | 'store';
}>(), {
    theme: 'default',
});
</script>

<template>
    <div
        v-if="pagination.last_page > 1"
        class="mt-4 flex flex-wrap gap-1"
    >
        <Link
            v-for="link in pagination.links"
            :key="`${link.label}-${link.url}`"
            :href="link.url ?? '#'"
            class="border px-3 py-1 text-sm transition"
            :class="
                theme === 'store'
                    ? link.active
                        ? 'rounded-full border-[var(--store-ink)] bg-[var(--store-ink)] px-4 py-2 text-white'
                        : 'rounded-full border-[var(--store-ink)]/20 bg-transparent px-4 py-2 text-[var(--store-ink)] hover:border-[var(--store-ink)]'
                    : link.active
                      ? 'rounded border-indigo-500 bg-indigo-500 text-white'
                      : 'rounded border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
            "
            :preserve-state="true"
            :view-transition="theme === 'store'"
            v-html="link.label"
        />
    </div>
</template>
