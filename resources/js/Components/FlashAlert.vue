<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const message = computed(() => page.props.flash?.success ?? page.props.flash?.error);
const isError = computed(() => Boolean(page.props.flash?.error));
</script>

<template>
    <div
        v-if="message"
        class="mb-4 rounded-md px-4 py-3 text-sm"
        :class="
            isError
                ? 'border border-red-200 bg-red-50 text-red-800'
                : 'border border-green-200 bg-green-50 text-green-800'
        "
    >
        {{ message }}
    </div>
</template>
