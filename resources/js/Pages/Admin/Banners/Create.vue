<script setup lang="ts">
import BannerForm from '@/Components/Admin/BannerForm.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { BannerFormData } from '@/types/content';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm<BannerFormData>({
    title: '',
    eyebrow: '',
    description: '',
    image: null,
    image_alt: '',
    cta_label: 'Explorar seleção',
    cta_url: '/',
    theme: 'paper',
    placement: 'hero',
    is_active: true,
    starts_at: '',
    ends_at: '',
    sort_order: 0,
    remove_image: false,
});

const submit = () => {
    form.post(route('admin.banners.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Novo banner" />

    <AdminLayout>
        <template #header>
            <div>
                <p class="text-sm text-gray-500">Conteúdo da loja</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Novo banner
                </h2>
            </div>
        </template>

        <FlashAlert />

        <form
            class="space-y-6 rounded-lg bg-white p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <BannerForm :form="form" />

            <div
                class="flex items-center gap-3 border-t border-gray-200 pt-5"
            >
                <PrimaryButton :disabled="form.processing">
                    Salvar banner
                </PrimaryButton>
                <Link
                    :href="route('admin.banners.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Cancelar
                </Link>
            </div>
        </form>
    </AdminLayout>
</template>
