<script setup lang="ts">
import type { Paginated } from '@/types/catalog';
import { Link } from '@inertiajs/vue3';

defineProps<{
    pagination: Paginated<unknown>;
}>();
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
            class="rounded border px-3 py-1 text-sm"
            :class="
                link.active
                    ? 'border-indigo-500 bg-indigo-500 text-white'
                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
            "
            :preserve-state="true"
            v-html="link.label"
        />
    </div>
</template>
