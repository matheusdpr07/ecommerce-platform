<script setup lang="ts">
import BannerForm from '@/Components/Admin/BannerForm.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Banner, BannerFormData } from '@/types/content';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    banner: Banner;
}>();

const form = useForm<BannerFormData>({
    title: props.banner.title,
    eyebrow: props.banner.eyebrow ?? '',
    description: props.banner.description ?? '',
    image: null,
    image_alt: props.banner.image_alt ?? '',
    cta_label: props.banner.cta_label ?? '',
    cta_url: props.banner.cta_url ?? '',
    theme: props.banner.theme,
    placement: props.banner.placement,
    is_active: props.banner.is_active,
    starts_at: props.banner.starts_at ?? '',
    ends_at: props.banner.ends_at ?? '',
    sort_order: props.banner.sort_order,
    remove_image: false,
});

const submit = () => {
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('admin.banners.update', props.banner.id), {
            forceFormData: true,
        });
};

const destroyBanner = () => {
    if (window.confirm('Excluir este banner permanentemente?')) {
        router.delete(route('admin.banners.destroy', props.banner.id));
    }
};
</script>

<template>
    <Head title="Editar banner" />

    <AdminLayout>
        <template #header>
            <div>
                <p class="text-sm text-gray-500">Conteúdo da loja</p>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Editar banner
                </h2>
            </div>
        </template>

        <FlashAlert />

        <form
            class="space-y-6 rounded-lg bg-white p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <BannerForm
                :form="form"
                :current-image-url="banner.image_url"
            />

            <div
                class="flex items-center justify-between gap-3 border-t border-gray-200 pt-5"
            >
                <div class="flex items-center gap-3">
                    <PrimaryButton :disabled="form.processing">
                        Salvar alterações
                    </PrimaryButton>
                    <Link
                        :href="route('admin.banners.index')"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Cancelar
                    </Link>
                </div>
                <button
                    type="button"
                    class="text-sm font-medium text-red-600 hover:text-red-800"
                    @click="destroyBanner"
                >
                    Excluir
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
