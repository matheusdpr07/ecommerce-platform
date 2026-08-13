<script setup lang="ts">
import StoreLayout from '@/Layouts/StoreLayout.vue';
import type { CartPayload } from '@/types/catalog';
import { formatMoneyFromCents } from '@/utils/money';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ cart: CartPayload }>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const couponForm = useForm({ code: '' });

const updateQuantity = (itemId: number, quantity: number) => {
    router.patch(
        route('store.cart.items.update', itemId),
        { quantity },
        { preserveScroll: true },
    );
};
const removeItem = (itemId: number) => {
    router.delete(route('store.cart.items.destroy', itemId), {
        preserveScroll: true,
    });
};
const clearCart = () => router.delete(route('store.cart.clear'));
const applyCoupon = () => {
    couponForm.post(route('store.cart.coupon.apply'), {
        preserveScroll: true,
        onSuccess: () => couponForm.reset(),
    });
};
const removeCoupon = () => {
    router.delete(route('store.cart.coupon.remove'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Carrinho" />

    <StoreLayout>
        <div
            class="mb-10 flex flex-col gap-5 border-b border-[var(--store-ink)]/15 pb-8 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-[var(--store-coral)]"
                >
                    Sua seleção
                </p>
                <h1
                    class="mt-2 font-serif text-6xl leading-none tracking-[-0.06em] sm:text-7xl"
                >
                    Carrinho
                </h1>
                <p class="mt-3 text-sm text-[var(--store-muted)]">
                    {{ cart.item_count }}
                    {{ cart.item_count === 1 ? 'item escolhido' : 'itens escolhidos' }}
                </p>
            </div>
            <button
                v-if="cart.items.length > 0"
                type="button"
                class="w-fit text-xs font-bold text-[var(--store-muted)] underline underline-offset-4"
                @click="clearCart"
            >
                Esvaziar carrinho
            </button>
        </div>

        <div
            v-if="cart.items.length > 0"
            class="grid gap-12 lg:grid-cols-[1fr_24rem] xl:gap-20"
        >
            <section>
                <article
                    v-for="item in cart.items"
                    :key="item.id"
                    class="store-reveal grid grid-cols-[7rem_1fr] gap-5 border-b border-[var(--store-ink)]/15 py-6 first:pt-0 sm:grid-cols-[10rem_1fr] sm:gap-7"
                >
                    <Link
                        :href="route('store.products.show', item.product.slug)"
                        view-transition
                        class="aspect-[4/5] overflow-hidden rounded-[1.5rem] bg-[var(--store-sand)]"
                    >
                        <img
                            v-if="item.product.primary_image"
                            :src="item.product.primary_image.url"
                            :alt="item.product.primary_image.alt_text ?? item.product.name"
                            loading="lazy"
                            decoding="async"
                            class="size-full object-cover"
                        />
                    </Link>

                    <div class="flex min-w-0 flex-col">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <Link
                                    :href="route('store.products.show', item.product.slug)"
                                    view-transition
                                    class="font-serif text-2xl leading-tight tracking-[-0.025em] sm:text-3xl"
                                >
                                    {{ item.product.name }}
                                </Link>
                                <p class="mt-2 text-xs text-[var(--store-muted)]">
                                    {{ item.variant.name }} · {{ item.variant.sku }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="text-xs font-semibold text-[var(--store-muted)] underline underline-offset-4"
                                @click="removeItem(item.id)"
                            >
                                Remover
                            </button>
                        </div>

                        <p
                            v-if="item.has_promotion"
                            class="mt-3 w-fit rounded-full bg-[var(--store-coral)]/10 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-[var(--store-coral)]"
                        >
                            Oferta aplicada
                        </p>
                        <p
                            v-if="!item.is_available"
                            class="mt-3 text-sm font-semibold text-red-600"
                        >
                            Quantidade indisponível. Ajuste para continuar.
                        </p>

                        <div
                            class="mt-auto flex flex-col gap-4 pt-5 sm:flex-row sm:items-end sm:justify-between"
                        >
                            <label class="w-fit">
                                <span
                                    class="text-[0.6rem] font-bold uppercase tracking-[0.15em] text-[var(--store-muted)]"
                                >
                                    Quantidade
                                </span>
                                <input
                                    type="number"
                                    min="1"
                                    :max="item.max_quantity"
                                    :value="item.quantity"
                                    class="mt-1 block w-20 rounded-full border-[var(--store-line)] bg-transparent px-4 text-center text-sm focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                                    @change="
                                        updateQuantity(
                                            item.id,
                                            Number(($event.target as HTMLInputElement).value),
                                        )
                                    "
                                />
                            </label>
                            <div class="sm:text-right">
                                <p
                                    v-if="item.original_unit_price_cents"
                                    class="text-xs text-[var(--store-muted)] line-through"
                                >
                                    {{ formatMoneyFromCents(item.original_unit_price_cents * item.quantity) }}
                                </p>
                                <p class="font-serif text-2xl tracking-[-0.03em]">
                                    {{ formatMoneyFromCents(item.line_total_cents) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </article>

                <div class="mt-10 rounded-[1.5rem] bg-[var(--store-paper)] p-5 sm:p-6">
                    <p
                        class="text-[0.65rem] font-bold uppercase tracking-[0.18em] text-[var(--store-muted)]"
                    >
                        Tem um cupom?
                    </p>
                    <form
                        v-if="!cart.coupon"
                        class="mt-3 flex flex-col gap-2 sm:flex-row"
                        @submit.prevent="applyCoupon"
                    >
                        <input
                            v-model="couponForm.code"
                            type="text"
                            placeholder="Digite o código"
                            class="block flex-1 rounded-full border-[var(--store-line)] bg-transparent px-5 uppercase focus:border-[var(--store-ink)] focus:ring-[var(--store-ink)]"
                        />
                        <button
                            type="submit"
                            class="rounded-full border border-[var(--store-ink)] px-6 py-3 text-sm font-bold"
                            :disabled="couponForm.processing"
                        >
                            Aplicar
                        </button>
                    </form>
                    <p v-if="couponForm.errors.code" class="mt-2 text-sm text-red-600">
                        {{ couponForm.errors.code }}
                    </p>
                    <div
                        v-if="cart.coupon"
                        class="mt-3 flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                    >
                        <span>
                            <strong>{{ cart.coupon.code }}</strong> · economia de
                            {{ formatMoneyFromCents(cart.coupon.discount_cents) }}
                        </span>
                        <button type="button" class="font-bold" @click="removeCoupon">
                            Remover
                        </button>
                    </div>
                </div>
            </section>

            <aside class="h-fit lg:sticky lg:top-28">
                <div
                    class="rounded-[2rem] bg-[var(--store-ink)] p-7 text-[var(--store-cream)] shadow-[0_25px_60px_rgba(56,36,13,0.2)]"
                >
                    <p
                        class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-[var(--store-accent)]"
                    >
                        Resumo
                    </p>
                    <dl class="mt-6 space-y-3 text-sm">
                        <div class="flex justify-between text-[var(--store-cream)]/65">
                            <dt>Subtotal</dt>
                            <dd>{{ formatMoneyFromCents(cart.subtotal_cents) }}</dd>
                        </div>
                        <div
                            v-if="cart.discount_cents > 0"
                            class="flex justify-between text-[var(--store-accent)]"
                        >
                            <dt>Desconto</dt>
                            <dd>-{{ formatMoneyFromCents(cart.discount_cents) }}</dd>
                        </div>
                        <div class="flex justify-between text-[var(--store-cream)]/65">
                            <dt>Frete</dt>
                            <dd>Calculado no checkout</dd>
                        </div>
                        <div
                            class="mt-5 flex items-end justify-between border-t border-[var(--store-cream)]/15 pt-5"
                        >
                            <dt class="font-bold">Total parcial</dt>
                            <dd class="font-serif text-3xl tracking-[-0.04em]">
                                {{ formatMoneyFromCents(cart.total_cents) }}
                            </dd>
                        </div>
                    </dl>

                    <Link
                        :href="route('store.checkout.index')"
                        class="mt-7 flex w-full items-center justify-between rounded-full bg-[var(--store-accent)] px-6 py-4 text-sm font-bold text-[var(--store-ink)] transition hover:-translate-y-0.5"
                    >
                        {{ user ? 'Continuar para entrega' : 'Entrar e continuar' }}
                        <span aria-hidden="true">→</span>
                    </Link>
                    <p class="mt-4 text-xs leading-5 text-[var(--store-cream)]/45">
                        Seu carrinho fica preservado durante o acesso. A cobrança
                        só é criada após a revisão final.
                    </p>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-2 text-center text-[0.62rem] font-semibold">
                    <span>✓ Compra segura</span>
                    <span>◇ Pix protegido</span>
                    <span>↗ Rastreável</span>
                </div>
            </aside>
        </div>

        <div
            v-else
            class="rounded-[2.5rem] border border-dashed border-[var(--store-ink)]/25 bg-[var(--store-paper)] px-6 py-20 text-center"
        >
            <span
                class="mx-auto grid size-16 place-items-center rounded-full bg-[var(--store-accent)] text-2xl"
                aria-hidden="true"
            >
                ◇
            </span>
            <p class="mt-6 font-serif text-4xl tracking-[-0.04em]">
                Espaço para novas descobertas
            </p>
            <p class="mt-2 text-sm text-[var(--store-muted)]">
                Seu carrinho está vazio. A curadoria está logo ali.
            </p>
            <Link
                :href="route('store.home')"
                view-transition
                class="mt-7 inline-flex rounded-full bg-[var(--store-ink)] px-7 py-3.5 text-sm font-bold text-[var(--store-cream)]"
            >
                Explorar produtos
            </Link>
        </div>
    </StoreLayout>
</template>
