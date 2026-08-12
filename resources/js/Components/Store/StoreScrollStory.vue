<script setup lang="ts">
import type { StoreCategoryOption, StoreProductSummary } from '@/types/catalog';
import type { StoreBanner } from '@/types/content';
import { formatMoneyFromCents } from '@/utils/money';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    banners: StoreBanner[];
    products: StoreProductSummary[];
    categories: StoreCategoryOption[];
}>();

const page = usePage();
const storyRoot = ref<HTMLElement | null>(null);
const activeScene = ref(0);
const storyReady = ref(false);
let observer: IntersectionObserver | null = null;

const heroBanner = computed(
    () => props.banners.find((banner) => banner.placement === 'hero') ?? null,
);
const editorialBanner = computed(
    () => props.banners.find((banner) => banner.placement === 'editorial') ?? null,
);
const heroProduct = computed(() => props.products[0] ?? null);
const featuredProducts = computed(() => props.products.slice(0, 3));
const featuredCategories = computed(() => props.categories.slice(0, 4));
const heroImage = computed(
    () => heroBanner.value?.image_url ?? heroProduct.value?.primary_image?.url ?? null,
);
const heroImageAlt = computed(
    () =>
        heroBanner.value?.image_alt ??
        heroBanner.value?.title ??
        heroProduct.value?.primary_image?.alt_text ??
        heroProduct.value?.name ??
        '',
);

const sceneContentClass = (scene: number) => ({
    'is-visible': !storyReady.value || activeScene.value === scene,
    'is-hidden': storyReady.value && activeScene.value !== scene,
});

onMounted(() => {
    storyReady.value = true;

    if (!storyRoot.value || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const visibility = new Map<Element, number>();
    const chapters = storyRoot.value.querySelectorAll<HTMLElement>('[data-story-chapter]');

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                visibility.set(entry.target, entry.isIntersecting ? entry.intersectionRatio : 0);
            });

            const current = [...visibility.entries()].sort(
                ([, first], [, second]) => second - first,
            )[0];

            if (current && current[1] > 0) {
                activeScene.value = Number(
                    (current[0] as HTMLElement).dataset.storyChapter,
                );
            }
        },
        {
            rootMargin: '-34% 0px -34% 0px',
            threshold: [0, 0.05, 0.1, 0.2, 0.3],
        },
    );

    chapters.forEach((chapter) => {
        visibility.set(chapter, 0);
        observer?.observe(chapter);
    });
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <section
        ref="storyRoot"
        class="store-story relative isolate"
        aria-labelledby="store-story-title"
    >
        <div class="store-story__stage" aria-hidden="true">
            <div
                class="store-story__scene store-story__scene--ink"
                :class="{ 'is-active': activeScene === 0 }"
            >
                <span class="store-story__halo store-story__halo--one" />
                <span class="store-story__outline-word">AUREA</span>
                <div class="store-story__hero-frame">
                    <img
                        v-if="heroImage"
                        :src="heroImage"
                        :alt="heroImageAlt"
                        fetchpriority="high"
                        decoding="async"
                    />
                    <div v-else class="store-story__abstract-product">
                        <span />
                        <span />
                    </div>
                </div>
            </div>

            <div
                class="store-story__scene store-story__scene--coral"
                :class="{ 'is-active': activeScene === 1 }"
            >
                <span class="store-story__orbit store-story__orbit--one" />
                <span class="store-story__orbit store-story__orbit--two" />
                <span class="store-story__scene-number">02</span>
                <span class="store-story__outline-word store-story__outline-word--dark">
                    DESCUBRA
                </span>
            </div>

            <div
                class="store-story__scene store-story__scene--accent"
                :class="{ 'is-active': activeScene === 2 }"
            >
                <span class="store-story__scene-number">03</span>
                <div class="store-story__product-orbit">
                    <div
                        v-for="(product, index) in featuredProducts"
                        :key="product.id"
                        class="store-story__product-frame"
                        :class="`store-story__product-frame--${index + 1}`"
                    >
                        <img
                            v-if="product.primary_image"
                            :src="product.primary_image.url"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        />
                        <span v-else>{{ String(index + 1).padStart(2, '0') }}</span>
                    </div>
                </div>
                <span class="store-story__outline-word store-story__outline-word--dark">
                    CURADORIA
                </span>
            </div>

            <div
                class="store-story__scene store-story__scene--paper"
                :class="{ 'is-active': activeScene === 3 }"
            >
                <span class="store-story__scene-number">04</span>
                <span class="store-story__halo store-story__halo--two" />
                <div
                    v-if="editorialBanner?.image_url"
                    class="store-story__editorial-frame"
                >
                    <img
                        :src="editorialBanner.image_url"
                        :alt="editorialBanner.image_alt ?? editorialBanner.title"
                        loading="lazy"
                        decoding="async"
                    />
                </div>
                <span class="store-story__outline-word store-story__outline-word--dark">
                    CONFIANÇA
                </span>
            </div>

            <ol class="store-story__progress">
                <li v-for="scene in 4" :key="scene">
                    <span
                        class="store-story__progress-dot"
                        :class="{ 'is-active': activeScene === scene - 1 }"
                    />
                    <span>{{ String(scene).padStart(2, '0') }}</span>
                </li>
            </ol>
        </div>

        <div class="store-story__chapters">
            <article
                class="store-story__chapter store-story__chapter--ink"
                data-story-chapter="0"
            >
                <div
                    class="store-story__content max-w-[46rem] text-[var(--store-paper)]"
                    :class="sceneContentClass(0)"
                >
                    <p class="store-story__eyebrow">
                        <span />
                        {{ heroBanner?.eyebrow ?? page.props.store.eyebrow }}
                    </p>
                    <h1 id="store-story-title" class="store-story__title">
                        {{ heroBanner?.title ?? 'O extraordinário mora nos detalhes.' }}
                    </h1>
                    <p class="store-story__description text-white/70">
                        {{
                            heroBanner?.description ??
                            `${page.props.store.tagline} Descubra uma seleção feita para transformar escolhas simples em experiências memoráveis.`
                        }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a
                            v-if="heroBanner?.cta_url && heroBanner.cta_label"
                            :href="heroBanner.cta_url"
                            class="store-story__primary-action"
                        >
                            {{ heroBanner.cta_label }}
                            <span aria-hidden="true">→</span>
                        </a>
                        <a v-else href="#catalogo" class="store-story__primary-action">
                            Explorar a edição
                            <span aria-hidden="true">↓</span>
                        </a>
                        <Link
                            :href="route('store.home', { sort: 'newest' })"
                            view-transition
                            class="store-story__secondary-action border-white/35 text-white"
                        >
                            Ver novidades
                        </Link>
                    </div>
                </div>
            </article>

            <article
                class="store-story__chapter store-story__chapter--coral"
                data-story-chapter="1"
            >
                <div
                    class="store-story__content ml-auto max-w-[47rem] text-[var(--store-ink)]"
                    :class="sceneContentClass(1)"
                >
                    <p class="store-story__eyebrow">
                        <span />
                        Capítulo 02 · seu universo
                    </p>
                    <h2 class="store-story__title">
                        Comece pelo que desperta sua curiosidade.
                    </h2>
                    <p class="store-story__description text-[var(--store-ink)]/70">
                        Cada categoria abre uma nova forma de descobrir produtos que combinam
                        com o seu momento.
                    </p>
                    <nav
                        v-if="featuredCategories.length > 0"
                        class="mt-8 grid max-w-2xl gap-2 sm:grid-cols-2"
                        aria-label="Categorias em destaque"
                    >
                        <Link
                            v-for="(category, index) in featuredCategories"
                            :key="category.id"
                            :href="route('store.categories.show', category.slug)"
                            view-transition
                            class="store-story__category-link"
                        >
                            <span>{{ String(index + 1).padStart(2, '0') }}</span>
                            <strong>{{ category.name }}</strong>
                            <span aria-hidden="true">→</span>
                        </Link>
                    </nav>
                    <a
                        v-else
                        href="#catalogo"
                        class="store-story__secondary-action mt-8 border-[var(--store-ink)]/40"
                    >
                        Descobrir o catálogo
                    </a>
                </div>
            </article>

            <article
                class="store-story__chapter store-story__chapter--accent"
                data-story-chapter="2"
            >
                <div
                    class="store-story__content max-w-[42rem] text-[var(--store-ink)]"
                    :class="sceneContentClass(2)"
                >
                    <p class="store-story__eyebrow">
                        <span />
                        Capítulo 03 · escolhas em destaque
                    </p>
                    <h2 class="store-story__title">
                        Uma curadoria com personalidade própria.
                    </h2>
                    <p class="store-story__description text-[var(--store-ink)]/65">
                        Menos ruído, mais intenção. Veja o que está conduzindo a edição atual.
                    </p>
                    <div v-if="featuredProducts.length > 0" class="mt-7 grid max-w-xl gap-2">
                        <Link
                            v-for="(product, index) in featuredProducts"
                            :key="product.id"
                            :href="route('store.products.show', product.slug)"
                            view-transition
                            class="store-story__product-link"
                        >
                            <span>{{ String(index + 1).padStart(2, '0') }}</span>
                            <strong>{{ product.name }}</strong>
                            <span>{{ formatMoneyFromCents(product.min_price_cents) }}</span>
                        </Link>
                    </div>
                </div>
            </article>

            <article
                class="store-story__chapter store-story__chapter--paper"
                data-story-chapter="3"
            >
                <div
                    class="store-story__content ml-auto max-w-[48rem] text-[var(--store-ink)]"
                    :class="sceneContentClass(3)"
                >
                    <p class="store-story__eyebrow">
                        <span />
                        Capítulo 04 · compra tranquila
                    </p>
                    <h2 class="store-story__title">
                        {{
                            editorialBanner?.title ??
                            'Da descoberta à sua porta, você acompanha tudo.'
                        }}
                    </h2>
                    <p class="store-story__description text-[var(--store-ink)]/65">
                        {{
                            editorialBanner?.description ??
                            'Escolha livremente, pague com segurança e acompanhe cada atualização sem sair da sua conta.'
                        }}
                    </p>
                    <dl class="mt-8 grid gap-3 sm:grid-cols-3">
                        <div class="store-story__trust-item">
                            <dt>01 · Explore</dt>
                            <dd>Conheça tudo antes de entrar.</dd>
                        </div>
                        <div class="store-story__trust-item">
                            <dt>02 · Escolha</dt>
                            <dd>Checkout claro e pagamento protegido.</dd>
                        </div>
                        <div class="store-story__trust-item">
                            <dt>03 · Acompanhe</dt>
                            <dd>Status do pedido em tempo real.</dd>
                        </div>
                    </dl>
                    <a
                        v-if="editorialBanner?.cta_url && editorialBanner.cta_label"
                        :href="editorialBanner.cta_url"
                        class="store-story__primary-action mt-8"
                    >
                        {{ editorialBanner.cta_label }}
                        <span aria-hidden="true">→</span>
                    </a>
                    <a v-else href="#catalogo" class="store-story__primary-action mt-8">
                        Encontrar meu próximo favorito
                        <span aria-hidden="true">↓</span>
                    </a>
                </div>
            </article>
        </div>
    </section>
</template>

<style scoped>
.store-story__stage {
    position: sticky;
    top: 4.75rem;
    height: calc(100svh - 4.75rem);
    min-height: 38rem;
    overflow: hidden;
}

.store-story__scene {
    position: absolute;
    inset: 0;
    overflow: hidden;
    opacity: 0;
    transform: scale(1.025);
    transition: opacity 700ms cubic-bezier(0.22, 1, 0.36, 1),
        transform 1100ms cubic-bezier(0.22, 1, 0.36, 1);
}

.store-story__scene.is-active {
    opacity: 1;
    transform: scale(1);
}

.store-story__scene--ink {
    background: radial-gradient(circle at 15% 12%, rgba(255, 107, 74, 0.2), transparent 30%),
        #171811;
}

.store-story__scene--coral { background: #ff6b4a; }
.store-story__scene--accent { background: #dfff4f; }
.store-story__scene--paper {
    background: radial-gradient(circle at 15% 45%, rgba(223, 255, 79, 0.55), transparent 25%),
        #f4f0e8;
}

.store-story__halo,
.store-story__orbit {
    position: absolute;
    display: block;
    border-radius: 999px;
}

.store-story__halo--one {
    right: -8vw;
    top: -14vw;
    width: min(58vw, 58rem);
    aspect-ratio: 1;
    border: clamp(2rem, 7vw, 7rem) solid rgba(223, 255, 79, 0.13);
}

.store-story__halo--two {
    left: -14vw;
    top: 9%;
    width: min(44vw, 44rem);
    aspect-ratio: 1;
    background: var(--store-accent);
}

.store-story__orbit { border: 1px solid rgba(23, 24, 17, 0.28); }
.store-story__orbit--one { left: -12vw; top: -22vw; width: 68vw; aspect-ratio: 1; }
.store-story__orbit--two { left: -4vw; top: -14vw; width: 52vw; aspect-ratio: 1; }

.store-story__hero-frame,
.store-story__editorial-frame {
    position: absolute;
    overflow: hidden;
    box-shadow: 0 2.5rem 7rem rgba(0, 0, 0, 0.25);
}

.store-story__hero-frame {
    right: clamp(2rem, 7vw, 8rem);
    top: 8%;
    width: min(39vw, 35rem);
    height: 84%;
    border-radius: 50% 50% 2.5rem 2.5rem;
    transform: rotate(2deg);
    background: #d9d2c5;
}

.store-story__hero-frame::after,
.store-story__editorial-frame::after {
    position: absolute;
    inset: 0;
    content: '';
    background: linear-gradient(140deg, transparent 45%, rgba(23, 24, 17, 0.32));
}

.store-story__hero-frame img,
.store-story__editorial-frame img,
.store-story__product-frame img { width: 100%; height: 100%; object-fit: cover; }

.store-story__editorial-frame {
    left: clamp(2rem, 6vw, 7rem);
    top: 12%;
    width: min(38vw, 34rem);
    height: 76%;
    border-radius: 2rem 50% 50% 2rem;
    transform: rotate(-2deg);
}

.store-story__abstract-product {
    position: relative;
    width: 100%;
    height: 100%;
    background: linear-gradient(155deg, #eee6d9, #b9ae9d);
}

.store-story__abstract-product span:first-child,
.store-story__abstract-product span:last-child {
    position: absolute;
    width: 86%;
    aspect-ratio: 1;
    border-radius: 999px;
}

.store-story__abstract-product span:first-child {
    left: -18%; top: 10%; background: var(--store-accent);
}
.store-story__abstract-product span:last-child {
    right: -20%; bottom: -10%; background: var(--store-coral);
}

.store-story__outline-word {
    position: absolute;
    left: -0.025em;
    bottom: -0.16em;
    color: transparent;
    font-family: Georgia, Cambria, 'Times New Roman', serif;
    font-size: clamp(8rem, 22vw, 25rem);
    font-weight: 700;
    line-height: 0.8;
    letter-spacing: -0.085em;
    white-space: nowrap;
    -webkit-text-stroke: 1px rgba(255, 255, 255, 0.09);
}

.store-story__outline-word--dark {
    -webkit-text-stroke-color: rgba(23, 24, 17, 0.1);
}

.store-story__scene-number {
    position: absolute;
    right: 5vw;
    top: 4vh;
    font-family: Georgia, Cambria, 'Times New Roman', serif;
    font-size: clamp(6rem, 14vw, 13rem);
    line-height: 1;
    opacity: 0.1;
}

.store-story__product-orbit {
    position: absolute;
    right: 2vw;
    top: 10%;
    width: min(48vw, 43rem);
    height: 82%;
}

.store-story__product-frame {
    position: absolute;
    display: grid;
    place-items: center;
    overflow: hidden;
    border: 0.55rem solid rgba(255, 253, 248, 0.92);
    border-radius: 1.5rem;
    background: #d8d0c2;
    color: rgba(23, 24, 17, 0.4);
    font-family: Georgia, Cambria, 'Times New Roman', serif;
    font-size: 5rem;
    box-shadow: 0 2rem 5rem rgba(23, 24, 17, 0.18);
}

.store-story__product-frame--1 { left: 20%; top: 2%; z-index: 3; width: 48%; height: 64%; transform: rotate(5deg); }
.store-story__product-frame--2 { left: 0; bottom: 2%; z-index: 2; width: 42%; height: 54%; transform: rotate(-8deg); }
.store-story__product-frame--3 { right: 0; bottom: 5%; z-index: 1; width: 42%; height: 56%; transform: rotate(11deg); }

.store-story__progress {
    position: absolute;
    right: 1.4rem;
    top: 50%;
    z-index: 10;
    display: grid;
    gap: 0.7rem;
    transform: translateY(-50%);
    font-size: 0.58rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    mix-blend-mode: difference;
}

.store-story__progress li {
    display: grid;
    grid-template-columns: 1rem 1.5rem;
    align-items: center;
    gap: 0.35rem;
    color: white;
}

.store-story__progress-dot {
    display: block;
    width: 0.25rem;
    height: 0.25rem;
    margin-left: auto;
    border-radius: 999px;
    background: currentColor;
    transition: width 350ms ease;
}

.store-story__progress-dot.is-active { width: 1rem; }

.store-story__chapters {
    position: relative;
    z-index: 5;
    margin-top: calc(-100svh + 4.75rem);
}

.store-story__chapter {
    display: flex;
    min-height: max(38rem, calc(100svh - 4.75rem));
    align-items: center;
    padding: clamp(4rem, 8vw, 8rem) clamp(1rem, 7vw, 7rem);
}

.store-story__content {
    position: relative;
    z-index: 10;
    width: 100%;
    transition: opacity 600ms cubic-bezier(0.22, 1, 0.36, 1),
        transform 850ms cubic-bezier(0.22, 1, 0.36, 1);
}

.store-story__content.is-visible { opacity: 1; transform: translateY(0); }
.store-story__content.is-hidden { pointer-events: none; opacity: 0; transform: translateY(2.5rem); }

.store-story__eyebrow {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1.3rem;
    font-size: 0.64rem;
    font-weight: 800;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

.store-story__eyebrow > span { width: 2.5rem; height: 1px; background: currentColor; }

.store-story__title {
    max-width: 13ch;
    font-family: Georgia, Cambria, 'Times New Roman', serif;
    font-size: clamp(3.2rem, 6.7vw, 7.5rem);
    line-height: 0.86;
    letter-spacing: -0.065em;
}

.store-story__description {
    max-width: 34rem;
    margin-top: 1.8rem;
    font-size: clamp(0.95rem, 1.3vw, 1.15rem);
    line-height: 1.7;
}

.store-story__primary-action,
.store-story__secondary-action {
    display: inline-flex;
    width: fit-content;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    border-radius: 999px;
    padding: 0.85rem 1.35rem;
    font-size: 0.83rem;
    font-weight: 800;
    transition: transform 250ms ease, background-color 250ms ease;
}

.store-story__primary-action { background: var(--store-ink); color: white; }
.store-story__primary-action:hover,
.store-story__secondary-action:hover { transform: translateY(-0.2rem); }
.store-story__secondary-action { border-width: 1px; border-style: solid; }

.store-story__category-link,
.store-story__product-link {
    display: grid;
    align-items: center;
    gap: 0.75rem;
    border-top: 1px solid rgba(23, 24, 17, 0.3);
    padding: 0.9rem 0.2rem;
    transition: padding 250ms ease;
}

.store-story__category-link,
.store-story__product-link { grid-template-columns: 2rem 1fr auto; }
.store-story__category-link:hover,
.store-story__product-link:hover { padding-inline: 0.7rem; }
.store-story__category-link > span,
.store-story__product-link > span { font-size: 0.7rem; font-weight: 700; }
.store-story__category-link strong,
.store-story__product-link strong {
    font-family: Georgia, Cambria, 'Times New Roman', serif;
    font-size: 1.15rem;
    font-weight: 400;
}

.store-story__trust-item { border-top: 1px solid rgba(23, 24, 17, 0.28); padding-top: 0.9rem; }
.store-story__trust-item dt { font-size: 0.68rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
.store-story__trust-item dd { margin-top: 0.55rem; font-size: 0.8rem; line-height: 1.55; opacity: 0.68; }

@media (max-width: 1023px) {
    .store-story__hero-frame,
    .store-story__editorial-frame,
    .store-story__product-orbit {
        right: -10%;
        bottom: -8%;
        top: auto;
        width: 62vw;
        height: 54%;
        opacity: 0.55;
    }
    .store-story__editorial-frame { left: -10%; right: auto; }
    .store-story__content { max-width: 42rem; }
    .store-story__title { max-width: 12ch; }
}

@media (max-width: 639px) {
    .store-story__stage { min-height: 35rem; }
    .store-story__chapter {
        min-height: max(35rem, calc(100svh - 4.75rem));
        padding-block: 3.5rem;
        padding-right: 2.4rem;
    }
    .store-story__title { font-size: clamp(2.75rem, 14vw, 4.6rem); }
    .store-story__description { max-width: 28rem; margin-top: 1.25rem; font-size: 0.9rem; }
    .store-story__hero-frame,
    .store-story__editorial-frame,
    .store-story__product-orbit { width: 78vw; height: 48%; opacity: 0.35; }
    .store-story__outline-word { font-size: 36vw; }
    .store-story__progress { right: 0.45rem; }
    .store-story__progress li { grid-template-columns: 0.75rem; }
    .store-story__progress li > span:last-child { display: none; }
    .store-story__category-link strong,
    .store-story__product-link strong { font-size: 1rem; }
    .store-story__product-link { grid-template-columns: 1.6rem 1fr; }
    .store-story__product-link > span:last-child { display: none; }
}

@media (prefers-reduced-motion: reduce) {
    .store-story__stage { display: none; }
    .store-story__chapters { margin-top: 0; }
    .store-story__chapter { min-height: auto; padding-block: 5rem; }
    .store-story__chapter--ink { background: #171811; }
    .store-story__chapter--coral { background: #ff6b4a; }
    .store-story__chapter--accent { background: #dfff4f; }
    .store-story__chapter--paper { background: #f4f0e8; }
    .store-story__content {
        pointer-events: auto !important;
        opacity: 1 !important;
        transform: none !important;
    }
}
</style>
