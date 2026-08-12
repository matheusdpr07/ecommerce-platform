<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Coupon } from '@/types/catalog';
import { formatCentsToBrl, parseBrlToCents } from '@/utils/money';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    coupon: Coupon;
}>();

const form = useForm({
    code: props.coupon.code,
    name: props.coupon.name,
    type: props.coupon.type,
    value:
        props.coupon.type === 'percentage'
            ? String(props.coupon.value)
            : '10',
    fixed_value:
        props.coupon.type === 'fixed_amount'
            ? formatCentsToBrl(props.coupon.value)
            : '10,00',
    min_order_cents: props.coupon.min_order_cents
        ? formatCentsToBrl(props.coupon.min_order_cents)
        : '',
    max_discount_cents: props.coupon.max_discount_cents
        ? formatCentsToBrl(props.coupon.max_discount_cents)
        : '',
    usage_limit: props.coupon.usage_limit
        ? String(props.coupon.usage_limit)
        : '',
    starts_at: props.coupon.starts_at?.slice(0, 16) ?? '',
    expires_at: props.coupon.expires_at?.slice(0, 16) ?? '',
    is_active: props.coupon.is_active,
});

const isPercentage = computed(() => form.type === 'percentage');

const submit = () => {
    form.transform((data) => ({
        ...data,
        value: isPercentage.value
            ? Number.parseInt(data.value, 10)
            : parseBrlToCents(data.fixed_value),
        min_order_cents: data.min_order_cents
            ? parseBrlToCents(data.min_order_cents)
            : null,
        max_discount_cents:
            isPercentage.value && data.max_discount_cents
                ? parseBrlToCents(data.max_discount_cents)
                : null,
        usage_limit: data.usage_limit
            ? Number.parseInt(data.usage_limit, 10)
            : null,
        starts_at: data.starts_at || null,
        expires_at: data.expires_at || null,
    })).put(route('admin.coupons.update', props.coupon.id));
};

const destroyCoupon = () => {
    router.delete(route('admin.coupons.destroy', props.coupon.id));
};
</script>

<template>
    <Head title="Editar cupom" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar cupom
            </h2>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="code" value="Codigo" />
                        <TextInput
                            id="code"
                            v-model="form.code"
                            class="mt-1 block w-full uppercase"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel for="name" value="Nome interno" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="type" value="Tipo de desconto" />
                        <select
                            id="type"
                            v-model="form.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="percentage">Percentual</option>
                            <option value="fixed_amount">Valor fixo</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel
                            :value="
                                isPercentage
                                    ? 'Percentual (1-100)'
                                    : 'Valor fixo (R$)'
                            "
                        />
                        <TextInput
                            v-if="isPercentage"
                            v-model="form.value"
                            type="number"
                            min="1"
                            max="100"
                            class="mt-1 block w-full"
                            required
                        />
                        <TextInput
                            v-else
                            v-model="form.fixed_value"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.value" />
                    </div>
                </div>

                <p class="text-sm text-gray-500">
                    Usos registrados: {{ coupon.usage_count }}
                </p>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <InputLabel value="Pedido minimo (R$)" />
                        <TextInput
                            v-model="form.min_order_cents"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <div v-if="isPercentage">
                        <InputLabel value="Desconto maximo (R$)" />
                        <TextInput
                            v-model="form.max_discount_cents"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <div>
                        <InputLabel value="Limite de usos" />
                        <TextInput
                            v-model="form.usage_limit"
                            type="number"
                            min="1"
                            class="mt-1 block w-full"
                        />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Inicio" />
                        <TextInput
                            v-model="form.starts_at"
                            type="datetime-local"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <div>
                        <InputLabel value="Fim" />
                        <TextInput
                            v-model="form.expires_at"
                            type="datetime-local"
                            class="mt-1 block w-full"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-gray-700">Cupom ativo</span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="form.processing">
                            Salvar
                        </PrimaryButton>
                        <Link
                            :href="route('admin.coupons.index')"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Cancelar
                        </Link>
                    </div>
                    <button
                        type="button"
                        class="text-sm text-red-600 hover:text-red-800"
                        @click="destroyCoupon"
                    >
                        Excluir
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
