<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Category, CategoryOption } from '@/types/catalog';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    category: Category;
    parentCategories: CategoryOption[];
}>();

const form = useForm({
    name: props.category.name,
    slug: props.category.slug,
    description: props.category.description ?? '',
    parent_id: props.category.parent_id ?? null,
    is_active: props.category.is_active,
    sort_order: String(props.category.sort_order),
    meta_title: props.category.meta_title ?? '',
    meta_description: props.category.meta_description ?? '',
});

const submit = () => {
    form.put(route('admin.categories.update', props.category.id));
};

const destroyCategory = () => {
    router.delete(route('admin.categories.destroy', props.category.id));
};
</script>

<template>
    <Head title="Editar categoria" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar categoria
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
                    <InputLabel for="parent_id" value="Categoria pai" />
                    <select
                        id="parent_id"
                        v-model="form.parent_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option :value="null">Nenhuma</option>
                        <option
                            v-for="parent in parentCategories"
                            :key="parent.id"
                            :value="parent.id"
                        >
                            {{ parent.name }}
                        </option>
                    </select>
                    <InputError
                        class="mt-2"
                        :message="form.errors.parent_id"
                    />
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

                <div>
                    <InputLabel for="sort_order" value="Ordem" />
                    <TextInput
                        id="sort_order"
                        v-model="form.sort_order"
                        type="number"
                        min="0"
                        class="mt-1 block w-full"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.sort_order"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-gray-700">Categoria ativa</span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="form.processing">
                            Salvar
                        </PrimaryButton>
                        <Link
                            :href="route('admin.categories.index')"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Cancelar
                        </Link>
                    </div>
                    <button
                        type="button"
                        class="text-sm text-red-600 hover:text-red-800"
                        @click="destroyCategory"
                    >
                        Excluir
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
