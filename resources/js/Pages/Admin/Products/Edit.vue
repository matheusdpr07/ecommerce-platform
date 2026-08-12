<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import FlashAlert from '@/Components/FlashAlert.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    CategoryOption,
    ProductFormData,
    ProductVariantForm,
} from '@/types/catalog';
import { formatCentsToBrl, parseBrlToCents } from '@/utils/money';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    product: ProductFormData;
    categories: CategoryOption[];
    brands: CategoryOption[];
}>();

const buildVariantForm = (
    variant: ProductFormData['variants'][number],
): ProductVariantForm => ({
    id: variant.id,
    sku: variant.sku,
    name: variant.name,
    price: formatCentsToBrl(variant.price_cents),
    compare_at_price: variant.compare_at_price_cents
        ? formatCentsToBrl(variant.compare_at_price_cents)
        : '',
    stock_quantity: String(variant.stock_quantity),
    is_active: variant.is_active,
    sort_order: String(variant.sort_order),
});

const form = useForm({
    name: props.product.name,
    slug: props.product.slug,
    description: props.product.description ?? '',
    category_id: props.product.category_id,
    brand_id: props.product.brand_id ?? null,
    is_active: props.product.is_active,
    meta_title: props.product.meta_title ?? '',
    meta_description: props.product.meta_description ?? '',
    variants: props.product.variants.map(buildVariantForm),
    images: [] as File[],
    remove_image_ids: [] as number[],
});

const addVariant = () => {
    form.variants.push({
        sku: '',
        name: 'Nova variacao',
        price: '0,00',
        compare_at_price: '',
        stock_quantity: '0',
        is_active: true,
        sort_order: String(form.variants.length),
    });
};

const removeVariant = (index: number) => {
    if (form.variants.length === 1) {
        return;
    }

    form.variants.splice(index, 1);
};

const onImagesSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.images = target.files ? Array.from(target.files) : [];
};

const markImageForRemoval = (imageId: number) => {
    if (!form.remove_image_ids.includes(imageId)) {
        form.remove_image_ids.push(imageId);
    }
};

const isImageMarkedForRemoval = (imageId: number) =>
    form.remove_image_ids.includes(imageId);

const visibleImages = () =>
    props.product.images.filter(
        (image) => !form.remove_image_ids.includes(image.id),
    );

const submit = () => {
    form
        .transform((data) => ({
            name: data.name,
            slug: data.slug,
            description: data.description,
            category_id: data.category_id,
            brand_id: data.brand_id,
            is_active: data.is_active,
            meta_title: data.meta_title,
            meta_description: data.meta_description,
            variants: data.variants.map((variant, index) => ({
                id: variant.id ?? null,
                sku: variant.sku,
                name: variant.name,
                price_cents: parseBrlToCents(variant.price),
                compare_at_price_cents: variant.compare_at_price
                    ? parseBrlToCents(variant.compare_at_price)
                    : null,
                stock_quantity:
                    Number.parseInt(variant.stock_quantity, 10) || 0,
                is_active: variant.is_active,
                sort_order: Number.parseInt(variant.sort_order, 10) || index,
            })),
            images: data.images,
            remove_image_ids: data.remove_image_ids,
            _method: 'put',
        }))
        .post(route('admin.products.update', props.product.id), {
            forceFormData: true,
        });
};
</script>

<template>
    <Head :title="`Editar ${product.name}`" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar produto
            </h2>
        </template>

        <FlashAlert />

        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
            <form class="space-y-6 p-6" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
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
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="category_id" value="Categoria" />
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        >
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.category_id"
                        />
                    </div>

                    <div>
                        <InputLabel for="brand_id" value="Marca (opcional)" />
                        <select
                            id="brand_id"
                            v-model="form.brand_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="null">Nenhuma</option>
                            <option
                                v-for="brand in brands"
                                :key="brand.id"
                                :value="brand.id"
                            >
                                {{ brand.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.brand_id"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel for="description" value="Descricao" />
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

                <div class="flex items-center gap-2">
                    <Checkbox v-model:checked="form.is_active" />
                    <span class="text-sm text-gray-700">Produto ativo</span>
                </div>

                <div>
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">
                            Variacoes
                        </h3>
                        <button
                            type="button"
                            class="text-sm text-indigo-600 hover:text-indigo-900"
                            @click="addVariant"
                        >
                            Adicionar variacao
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(variant, index) in form.variants"
                            :key="variant.id ?? `new-${index}`"
                            class="rounded-lg border border-gray-200 p-4"
                        >
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">
                                    Variacao {{ index + 1 }}
                                </span>
                                <button
                                    v-if="form.variants.length > 1"
                                    type="button"
                                    class="text-sm text-red-600 hover:text-red-800"
                                    @click="removeVariant(index)"
                                >
                                    Remover
                                </button>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <InputLabel
                                        :for="`variant-sku-${index}`"
                                        value="SKU"
                                    />
                                    <TextInput
                                        :id="`variant-sku-${index}`"
                                        v-model="variant.sku"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="
                                            (form.errors as Record<string, string>)[
                                                `variants.${index}.sku`
                                            ]
                                        "
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        :for="`variant-name-${index}`"
                                        value="Nome da variacao"
                                    />
                                    <TextInput
                                        :id="`variant-name-${index}`"
                                        v-model="variant.name"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        :for="`variant-price-${index}`"
                                        value="Preco (R$)"
                                    />
                                    <TextInput
                                        :id="`variant-price-${index}`"
                                        v-model="variant.price"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                </div>

                                <div>
                                    <InputLabel
                                        :for="`variant-stock-${index}`"
                                        value="Estoque"
                                    />
                                    <TextInput
                                        :id="`variant-stock-${index}`"
                                        v-model="variant.stock_quantity"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                </div>

                                <div class="flex items-end pb-2">
                                    <label class="flex items-center gap-2">
                                        <Checkbox
                                            v-model:checked="variant.is_active"
                                        />
                                        <span class="text-sm text-gray-700"
                                            >Variacao ativa</span
                                        >
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-3 text-lg font-medium text-gray-900">
                        Imagens atuais
                    </h3>

                    <div
                        v-if="visibleImages().length > 0"
                        class="grid grid-cols-2 gap-4 md:grid-cols-4"
                    >
                        <div
                            v-for="image in product.images"
                            :key="image.id"
                            class="relative rounded-lg border border-gray-200 p-2"
                            :class="{
                                'opacity-40': isImageMarkedForRemoval(
                                    image.id,
                                ),
                            }"
                        >
                            <img
                                :src="image.url"
                                :alt="image.alt_text ?? product.name"
                                class="h-32 w-full rounded object-cover"
                            />
                            <button
                                type="button"
                                class="mt-2 w-full text-sm text-red-600 hover:text-red-800"
                                @click="markImageForRemoval(image.id)"
                            >
                                {{
                                    isImageMarkedForRemoval(image.id)
                                        ? 'Marcada para remover'
                                        : 'Remover'
                                }}
                            </button>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500">
                        Nenhuma imagem cadastrada.
                    </p>
                </div>

                <div>
                    <InputLabel for="images" value="Adicionar imagens" />
                    <input
                        id="images"
                        type="file"
                        accept="image/*"
                        multiple
                        class="mt-1 block w-full text-sm text-gray-600"
                        @change="onImagesSelected"
                    />
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton :disabled="form.processing">
                        Salvar
                    </PrimaryButton>
                    <Link
                        :href="route('admin.products.index')"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
