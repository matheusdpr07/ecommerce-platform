<script setup lang="ts">
import type { ProductReviewsPayload } from '@/types/review';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    productSlug: string;
    reviews: ProductReviewsPayload;
}>();

const page = usePage();
const formOpen = ref(false);
const existingReview = computed(
    () => props.reviews.eligibility.existing_review,
);
const canWrite = computed(
    () =>
        props.reviews.eligibility.can_review ||
        props.reviews.eligibility.can_edit,
);

const form = useForm({
    rating: existingReview.value?.rating ?? 0,
    title: existingReview.value?.title ?? '',
    body: existingReview.value?.body ?? '',
});

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    };

    if (existingReview.value) {
        form.put(route('store.reviews.update', existingReview.value.id), options);
        return;
    }

    form.post(route('store.products.reviews.store', props.productSlug), options);
};

const formatDate = (isoDate?: string | null) => {
    if (!isoDate) {
        return '';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(isoDate));
};
</script>

<template>
    <section
        id="avaliacoes"
        class="scroll-mt-32 border-t border-[var(--store-ink)]/15 py-16 lg:py-24"
        aria-labelledby="reviews-heading"
    >
        <div class="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
            <div class="lg:sticky lg:top-32 lg:self-start">
                <p
                    class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-coral)]"
                >
                    Experiências reais
                </p>
                <h2
                    id="reviews-heading"
                    class="mt-3 font-serif text-5xl leading-none tracking-[-0.05em]"
                >
                    O que quem comprou achou
                </h2>

                <div class="mt-8 flex items-end gap-4">
                    <span
                        class="font-serif text-7xl leading-none tracking-[-0.06em]"
                    >
                        {{ reviews.summary.average.toFixed(1) }}
                    </span>
                    <div class="pb-1">
                        <p
                            class="text-xl tracking-[0.08em] text-[var(--store-coral)]"
                            :aria-label="`${reviews.summary.average} de 5 estrelas`"
                        >
                            {{ '★'.repeat(Math.round(reviews.summary.average))
                            }}<span class="text-[var(--store-line)]">{{
                                '★'.repeat(5 - Math.round(reviews.summary.average))
                            }}</span>
                        </p>
                        <p class="mt-1 text-xs text-[var(--store-muted)]">
                            {{ reviews.summary.total }}
                            {{
                                reviews.summary.total === 1
                                    ? 'avaliação publicada'
                                    : 'avaliações publicadas'
                            }}
                        </p>
                    </div>
                </div>

                <div class="mt-7 space-y-2">
                    <div
                        v-for="item in reviews.summary.distribution"
                        :key="item.rating"
                        class="grid grid-cols-[2.5rem_1fr_2rem] items-center gap-3 text-xs"
                    >
                        <span>{{ item.rating }} ★</span>
                        <span
                            class="h-1.5 overflow-hidden rounded-full bg-[var(--store-line)]"
                        >
                            <span
                                class="block h-full rounded-full bg-[var(--store-ink)]"
                                :style="{ width: `${item.percentage}%` }"
                            />
                        </span>
                        <span class="text-right text-[var(--store-muted)]">
                            {{ item.count }}
                        </span>
                    </div>
                </div>

                <div class="mt-8">
                    <button
                        v-if="canWrite"
                        type="button"
                        class="rounded-full bg-[var(--store-ink)] px-6 py-3 text-sm font-bold text-[var(--store-cream)]"
                        @click="formOpen = !formOpen"
                    >
                        {{ existingReview ? 'Editar minha avaliação' : 'Avaliar produto' }}
                    </button>
                    <p
                        v-else-if="reviews.eligibility.reason"
                        class="max-w-sm text-sm leading-6 text-[var(--store-muted)]"
                    >
                        {{ reviews.eligibility.reason }}
                        <Link
                            v-if="!page.props.auth.user"
                            :href="route('login')"
                            class="font-bold text-[var(--store-ink)] underline underline-offset-4"
                        >
                            Entrar
                        </Link>
                    </p>
                </div>

                <div
                    v-if="existingReview"
                    class="mt-5 rounded-2xl border border-[var(--store-ink)]/10 bg-[var(--store-paper)] p-4 text-sm"
                >
                    <p class="font-bold">{{ existingReview.status_label }}</p>
                    <p
                        v-if="existingReview.status === 'pending'"
                        class="mt-1 text-[var(--store-muted)]"
                    >
                        Nossa equipe está revisando o conteúdo antes da publicação.
                    </p>
                    <p
                        v-if="existingReview.moderation_notes"
                        class="mt-2 text-[var(--store-coral)]"
                    >
                        {{ existingReview.moderation_notes }}
                    </p>
                </div>
            </div>

            <div>
                <form
                    v-if="formOpen && canWrite"
                    class="mb-10 rounded-[2rem] bg-[var(--store-paper)] p-6 shadow-sm sm:p-8"
                    @submit.prevent="submit"
                >
                    <div>
                        <p class="text-sm font-bold">Sua nota</p>
                        <div class="mt-2 flex gap-1" role="radiogroup">
                            <button
                                v-for="rating in 5"
                                :key="rating"
                                type="button"
                                role="radio"
                                :aria-checked="form.rating === rating"
                                :aria-label="`${rating} estrelas`"
                                class="text-3xl transition hover:scale-110"
                                :class="
                                    rating <= form.rating
                                        ? 'text-[var(--store-coral)]'
                                        : 'text-[var(--store-line)]'
                                "
                                @click="form.rating = rating"
                            >
                                ★
                            </button>
                        </div>
                        <p v-if="form.errors.rating" class="mt-1 text-sm text-red-600">
                            {{ form.errors.rating }}
                        </p>
                    </div>

                    <label class="mt-5 block">
                        <span class="text-sm font-bold">Título (opcional)</span>
                        <input
                            v-model="form.title"
                            type="text"
                            maxlength="120"
                            class="mt-2 block w-full rounded-xl border-[var(--store-line)] bg-transparent focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                            placeholder="Resuma sua experiência"
                        />
                    </label>

                    <label class="mt-5 block">
                        <span class="text-sm font-bold">Sua experiência</span>
                        <textarea
                            v-model="form.body"
                            rows="5"
                            maxlength="2000"
                            class="mt-2 block w-full rounded-xl border-[var(--store-line)] bg-transparent focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                            placeholder="O que mais gostou? Como foi usar o produto?"
                        />
                        <span class="mt-1 flex justify-between text-xs">
                            <span class="text-red-600">{{ form.errors.body }}</span>
                            <span class="text-[var(--store-muted)]">
                                {{ form.body.length }}/2000
                            </span>
                        </span>
                    </label>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            class="rounded-full bg-[var(--store-ink)] px-6 py-3 text-sm font-bold text-[var(--store-cream)] disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Enviar para moderação
                        </button>
                        <button
                            type="button"
                            class="px-4 py-3 text-sm font-semibold text-[var(--store-muted)]"
                            @click="formOpen = false"
                        >
                            Cancelar
                        </button>
                    </div>
                </form>

                <div v-if="reviews.items.length > 0" class="space-y-5">
                    <article
                        v-for="review in reviews.items"
                        :key="review.id"
                        class="store-reveal border-b border-[var(--store-ink)]/15 pb-8"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <p
                                class="tracking-[0.08em] text-[var(--store-coral)]"
                                :aria-label="`${review.rating} de 5 estrelas`"
                            >
                                {{ '★'.repeat(review.rating) }}<span
                                    class="text-[var(--store-line)]"
                                    >{{ '★'.repeat(5 - review.rating) }}</span
                                >
                            </p>
                            <time class="text-xs text-[var(--store-muted)]">
                                {{ formatDate(review.created_at) }}
                            </time>
                        </div>
                        <h3
                            v-if="review.title"
                            class="mt-4 font-serif text-2xl tracking-[-0.02em]"
                        >
                            {{ review.title }}
                        </h3>
                        <p class="mt-3 whitespace-pre-line text-sm leading-7">
                            {{ review.body }}
                        </p>
                        <div class="mt-5 flex flex-wrap items-center gap-3 text-xs">
                            <span class="font-bold">{{ review.reviewer_name }}</span>
                            <span
                                v-if="review.is_verified_purchase"
                                class="rounded-full bg-emerald-100 px-3 py-1 font-semibold text-emerald-800"
                            >
                                ✓ Compra verificada
                            </span>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="rounded-[2rem] border border-dashed border-[var(--store-ink)]/25 p-10 text-center"
                >
                    <p class="font-serif text-3xl">A história começa aqui</p>
                    <p class="mt-2 text-sm text-[var(--store-muted)]">
                        Este produto ainda não recebeu avaliações publicadas.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
