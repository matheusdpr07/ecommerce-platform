<script setup lang="ts">
import FlashAlert from '@/Components/FlashAlert.vue';
import PaginationLinks from '@/Components/PaginationLinks.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Paginated } from '@/types/catalog';
import type {
    AdminReview,
    ReviewFilters,
    ReviewStatus,
} from '@/types/review';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps<{
    reviews: Paginated<AdminReview>;
    filters: ReviewFilters;
}>();

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const rating = ref(props.filters.rating);
const moderationNotes = reactive<Record<number, string>>(
    Object.fromEntries(
        props.reviews.data.map((review) => [
            review.id,
            review.moderation_notes ?? '',
        ]),
    ),
);

const applyFilters = () => {
    router.get(
        route('admin.reviews.index'),
        { search: search.value, status: status.value, rating: rating.value },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    rating.value = '';
    applyFilters();
};

const moderate = (review: AdminReview, nextStatus: ReviewStatus) => {
    router.patch(
        route('admin.reviews.update', review.id),
        {
            status: nextStatus,
            moderation_notes: moderationNotes[review.id],
        },
        { preserveScroll: true },
    );
};

const statusClasses = (reviewStatus: ReviewStatus) =>
    ({
        pending: 'bg-amber-100 text-amber-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    })[reviewStatus];

const formatDate = (isoDate?: string | null) =>
    isoDate
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(isoDate))
        : '';
</script>

<template>
    <Head title="Avaliações" />

    <AdminLayout>
        <template #header>
            <div>
                <p class="text-sm text-gray-500">Confiança e comunidade</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Moderação de avaliações
                </h2>
            </div>
        </template>

        <FlashAlert />

        <div class="overflow-hidden rounded-lg bg-white shadow-sm">
            <form
                class="grid gap-3 border-b border-gray-200 p-4 md:grid-cols-[1fr_auto_auto_auto] md:items-end"
                @submit.prevent="applyFilters"
            >
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Buscar
                    </label>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cliente, produto ou conteúdo"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Status
                    </label>
                    <select
                        v-model="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Todos</option>
                        <option value="pending">Aguardando moderação</option>
                        <option value="approved">Publicadas</option>
                        <option value="rejected">Não aprovadas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Nota
                    </label>
                    <select
                        v-model="rating"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">Todas</option>
                        <option v-for="value in 5" :key="value" :value="value">
                            {{ value }} estrelas
                        </option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <PrimaryButton type="submit">Filtrar</PrimaryButton>
                    <button
                        type="button"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        @click="clearFilters"
                    >
                        Limpar
                    </button>
                </div>
            </form>

            <div v-if="reviews.data.length > 0" class="divide-y divide-gray-200">
                <article
                    v-for="review in reviews.data"
                    :key="review.id"
                    class="grid gap-5 p-5 lg:grid-cols-[1fr_20rem]"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="tracking-wide text-amber-500">
                                {{ '★'.repeat(review.rating) }}<span
                                    class="text-gray-200"
                                    >{{ '★'.repeat(5 - review.rating) }}</span
                                >
                            </span>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusClasses(review.status)"
                            >
                                {{ review.status_label }}
                            </span>
                            <span
                                v-if="review.is_verified_purchase"
                                class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700"
                            >
                                Compra verificada
                            </span>
                        </div>
                        <h3 v-if="review.title" class="mt-4 font-semibold text-gray-900">
                            {{ review.title }}
                        </h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">
                            {{ review.body }}
                        </p>
                        <div class="mt-4 text-xs text-gray-500">
                            <span class="font-medium text-gray-700">
                                {{ review.user.name }}
                            </span>
                            · {{ review.user.email }} ·
                            <Link
                                :href="route('store.products.show', review.product.slug)"
                                class="font-medium text-indigo-600"
                            >
                                {{ review.product.name }}
                            </Link>
                            · {{ formatDate(review.created_at) }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <label
                            :for="`notes-${review.id}`"
                            class="text-sm font-medium text-gray-700"
                        >
                            Nota interna / retorno ao cliente
                        </label>
                        <textarea
                            :id="`notes-${review.id}`"
                            v-model="moderationNotes[review.id]"
                            rows="3"
                            class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Opcional ao aprovar; recomendado ao rejeitar"
                        />
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-md bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700"
                                @click="moderate(review, 'approved')"
                            >
                                Aprovar
                            </button>
                            <button
                                type="button"
                                class="rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700"
                                @click="moderate(review, 'rejected')"
                            >
                                Não aprovar
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="p-12 text-center text-sm text-gray-500">
                Nenhuma avaliação encontrada.
            </div>

            <div class="border-t border-gray-200 p-4">
                <PaginationLinks :pagination="reviews" />
            </div>
        </div>
    </AdminLayout>
</template>
