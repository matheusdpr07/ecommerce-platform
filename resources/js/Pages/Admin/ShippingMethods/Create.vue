<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { formatCentsToBrl, parseBrlToCents } from '@/utils/money';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
    price: '15,00',
    free_above: '',
    min_order: '',
    max_order: '',
    estimated_days_min: '3',
    estimated_days_max: '10',
    sort_order: '0',
    is_active: true,
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
    })).post(route('admin.shipping-methods.store'));
};
</script>

<template>
    <Head title="Novo metodo de frete" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Novo metodo de frete
            </h2>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <div>
                    <InputLabel for="name" value="Nome" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="description" value="Descricao" />
                    <TextInput id="description" v-model="form.description" class="mt-1 block w-full" />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="price" value="Valor (R$)" />
                        <TextInput id="price" v-model="form.price" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.price" />
                    </div>
                    <div>
                        <InputLabel for="free_above" value="Gratis acima de (R$)" />
                        <TextInput id="free_above" v-model="form.free_above" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="estimated_days_min" value="Prazo minimo (dias)" />
                        <TextInput id="estimated_days_min" v-model="form.estimated_days_min" type="number" min="1" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="estimated_days_max" value="Prazo maximo (dias)" />
                        <TextInput id="estimated_days_max" v-model="form.estimated_days_max" type="number" min="1" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-gray-700">Metodo ativo</span>
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton :disabled="form.processing">Salvar</PrimaryButton>
                    <Link :href="route('admin.shipping-methods.index')" class="text-sm text-gray-600 hover:text-gray-900">
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
