<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { ShippingMethodItem } from '@/types/catalog';
import { formatCentsToBrl, parseBrlToCents } from '@/utils/money';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    shippingMethod: ShippingMethodItem;
}>();

const form = useForm({
    name: props.shippingMethod.name,
    description: props.shippingMethod.description ?? '',
    price: formatCentsToBrl(props.shippingMethod.price_cents),
    free_above: props.shippingMethod.free_above_cents
        ? formatCentsToBrl(props.shippingMethod.free_above_cents)
        : '',
    min_order: props.shippingMethod.min_order_cents
        ? formatCentsToBrl(props.shippingMethod.min_order_cents)
        : '',
    max_order: props.shippingMethod.max_order_cents
        ? formatCentsToBrl(props.shippingMethod.max_order_cents)
        : '',
    estimated_days_min: props.shippingMethod.estimated_days_min
        ? String(props.shippingMethod.estimated_days_min)
        : '',
    estimated_days_max: props.shippingMethod.estimated_days_max
        ? String(props.shippingMethod.estimated_days_max)
        : '',
    sort_order: String(props.shippingMethod.sort_order),
    is_active: props.shippingMethod.is_active,
});

const submit = () => {
    form.transform((data) => ({
        name: data.name,
        description: data.description || null,
        price_cents: parseBrlToCents(data.price),
        free_above_cents: data.free_above
            ? parseBrlToCents(data.free_above)
            : null,
        min_order_cents: data.min_order ? parseBrlToCents(data.min_order) : null,
        max_order_cents: data.max_order ? parseBrlToCents(data.max_order) : null,
        estimated_days_min: data.estimated_days_min
            ? Number.parseInt(data.estimated_days_min, 10)
            : null,
        estimated_days_max: data.estimated_days_max
            ? Number.parseInt(data.estimated_days_max, 10)
            : null,
        sort_order: Number.parseInt(data.sort_order, 10),
        is_active: data.is_active,
    })).put(route('admin.shipping-methods.update', props.shippingMethod.id));
};

const destroyMethod = () => {
    router.delete(route('admin.shipping-methods.destroy', props.shippingMethod.id));
};
</script>

<template>
    <Head title="Editar metodo de frete" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar metodo de frete
            </h2>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <div>
                    <InputLabel value="Nome" />
                    <TextInput v-model="form.name" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel value="Descricao" />
                    <TextInput v-model="form.description" class="mt-1 block w-full" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Valor (R$)" />
                        <TextInput v-model="form.price" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.price" />
                    </div>
                    <div>
                        <InputLabel value="Gratis acima de (R$)" />
                        <TextInput v-model="form.free_above" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Prazo minimo (dias)" />
                        <TextInput v-model="form.estimated_days_min" type="number" min="1" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel value="Prazo maximo (dias)" />
                        <TextInput v-model="form.estimated_days_max" type="number" min="1" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-gray-700">Metodo ativo</span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="form.processing">Salvar</PrimaryButton>
                        <Link :href="route('admin.shipping-methods.index')" class="text-sm text-gray-600 hover:text-gray-900">
                            Cancelar
                        </Link>
                    </div>
                    <button type="button" class="text-sm text-red-600 hover:text-red-800" @click="destroyMethod">
                        Excluir
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
