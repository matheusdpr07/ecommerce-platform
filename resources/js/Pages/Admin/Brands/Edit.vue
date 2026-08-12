<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Brand } from '@/types/catalog';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    brand: Brand;
}>();

const form = useForm({
    name: props.brand.name,
    slug: props.brand.slug,
    description: props.brand.description ?? '',
    is_active: props.brand.is_active,
    meta_title: props.brand.meta_title ?? '',
    meta_description: props.brand.meta_description ?? '',
});

const submit = () => {
    form.put(route('admin.brands.update', props.brand.id));
};

const destroyBrand = () => {
    router.delete(route('admin.brands.destroy', props.brand.id));
};
</script>

<template>
    <Head title="Editar marca" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar marca
            </h2>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <form class="space-y-4 p-6" @submit.prevent="submit">
                <div>
                    <InputLabel for="name" value="Nome" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="slug" value="Slug (opcional)" />
                    <TextInput
                        id="slug"
                        v-model="form.slug"
                        class="mt-1 block w-full"
                    />
                    <InputError class="mt-2" :message="form.errors.slug" />
                </div>

                <div>
                    <InputLabel for="description" value="Descricao" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.description"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-gray-700">Marca ativa</span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="form.processing">
                            Salvar
                        </PrimaryButton>
                        <Link
                            :href="route('admin.brands.index')"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Cancelar
                        </Link>
                    </div>
                    <button
                        type="button"
                        class="text-sm text-red-600 hover:text-red-800"
                        @click="destroyBrand"
                    >
                        Excluir
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
