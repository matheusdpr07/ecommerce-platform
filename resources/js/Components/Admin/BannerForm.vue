<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import type { BannerFormData } from '@/types/content';
import type { InertiaForm } from '@inertiajs/vue3';

defineProps<{
    form: InertiaForm<BannerFormData>;
    currentImageUrl?: string | null;
}>();

const onImageSelected = (
    form: InertiaForm<BannerFormData>,
    event: Event,
) => {
    const target = event.target as HTMLInputElement;
    form.image = target.files?.[0] ?? null;

    if (form.image) {
        form.remove_image = false;
    }
};
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-5">
            <div>
                <InputLabel for="title" value="Título" />
                <TextInput
                    id="title"
                    v-model="form.title"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.title" />
            </div>

            <div>
                <InputLabel for="eyebrow" value="Chamada curta" />
                <TextInput
                    id="eyebrow"
                    v-model="form.eyebrow"
                    class="mt-1 block w-full"
                    placeholder="Ex.: Nova coleção"
                />
                <InputError class="mt-2" :message="form.errors.eyebrow" />
            </div>

            <div>
                <InputLabel for="description" value="Descrição" />
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.description"
                />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="cta_label" value="Texto do botão" />
                    <TextInput
                        id="cta_label"
                        v-model="form.cta_label"
                        class="mt-1 block w-full"
                        placeholder="Explorar seleção"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.cta_label"
                    />
                </div>
                <div>
                    <InputLabel for="cta_url" value="Destino do botão" />
                    <TextInput
                        id="cta_url"
                        v-model="form.cta_url"
                        class="mt-1 block w-full"
                        placeholder="/ ou https://..."
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.cta_url"
                    />
                </div>
            </div>
        </div>

        <div class="space-y-5 rounded-lg bg-gray-50 p-5">
            <div>
                <InputLabel for="image" value="Imagem" />
                <img
                    v-if="currentImageUrl && !form.remove_image"
                    :src="currentImageUrl"
                    alt="Imagem atual do banner"
                    class="mt-2 aspect-[4/3] w-full rounded-lg object-cover"
                />
                <input
                    id="image"
                    type="file"
                    accept="image/*"
                    class="mt-2 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-medium file:text-indigo-700"
                    @change="onImageSelected(form, $event)"
                />
                <InputError class="mt-2" :message="form.errors.image" />
                <label
                    v-if="currentImageUrl"
                    class="mt-3 flex items-center gap-2 text-sm text-gray-600"
                >
                    <Checkbox v-model:checked="form.remove_image" />
                    Remover imagem atual
                </label>
            </div>

            <div>
                <InputLabel for="image_alt" value="Texto alternativo" />
                <TextInput
                    id="image_alt"
                    v-model="form.image_alt"
                    class="mt-1 block w-full"
                    placeholder="Descreva o conteúdo da imagem"
                />
                <InputError class="mt-2" :message="form.errors.image_alt" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="placement" value="Posição" />
                    <select
                        id="placement"
                        v-model="form.placement"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="hero">Destaque principal</option>
                        <option value="editorial">Bloco editorial</option>
                    </select>
                    <InputError
                        class="mt-2"
                        :message="form.errors.placement"
                    />
                </div>
                <div>
                    <InputLabel for="theme" value="Tema visual" />
                    <select
                        id="theme"
                        v-model="form.theme"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="paper">Claro</option>
                        <option value="ink">Escuro</option>
                        <option value="accent">Destaque</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.theme" />
                </div>
            </div>

            <div>
                <InputLabel for="sort_order" value="Ordem de exibição" />
                <input
                    id="sort_order"
                    v-model.number="form.sort_order"
                    type="number"
                    min="0"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError class="mt-2" :message="form.errors.sort_order" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="starts_at" value="Início (opcional)" />
                    <input
                        id="starts_at"
                        v-model="form.starts_at"
                        type="datetime-local"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.starts_at"
                    />
                </div>
                <div>
                    <InputLabel for="ends_at" value="Término (opcional)" />
                    <input
                        id="ends_at"
                        v-model="form.ends_at"
                        type="datetime-local"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError class="mt-2" :message="form.errors.ends_at" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <Checkbox v-model:checked="form.is_active" />
                Banner ativo
            </label>
        </div>
    </div>
</template>
