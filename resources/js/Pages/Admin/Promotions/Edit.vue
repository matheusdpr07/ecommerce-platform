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
    Promotion,
    PromotionScopeOption,
} from '@/types/catalog';
import { formatCentsToBrl, parseBrlToCents } from '@/utils/money';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    promotion: Promotion;
    scopeOptions: PromotionScopeOption[];
    categories: CategoryOption[];
    brands: CategoryOption[];
    products: CategoryOption[];
}>();

const form = useForm({
    name: props.promotion.name,
    type: props.promotion.type,
    value:
        props.promotion.type === 'percentage'
            ? String(props.promotion.value)
            : '10',
    fixed_value:
        props.promotion.type === 'fixed_amount'
            ? formatCentsToBrl(props.promotion.value)
            : '10,00',
    scope: props.promotion.scope,
    category_id: props.promotion.category_id
        ? String(props.promotion.category_id)
        : '',
    brand_id: props.promotion.brand_id ? String(props.promotion.brand_id) : '',
    product_id: props.promotion.product_id
        ? String(props.promotion.product_id)
        : '',
    priority: String(props.promotion.priority),
    starts_at: props.promotion.starts_at?.slice(0, 16) ?? '',
    expires_at: props.promotion.expires_at?.slice(0, 16) ?? '',
    is_active: props.promotion.is_active,
});

const isPercentage = computed(() => form.type === 'percentage');

const submit = () => {
    form.transform((data) => ({
        ...data,
        value: isPercentage.value
            ? Number.parseInt(data.value, 10)
            : parseBrlToCents(data.fixed_value),
        category_id: data.scope === 'category' ? Number(data.category_id) : null,
        brand_id: data.scope === 'brand' ? Number(data.brand_id) : null,
        product_id: data.scope === 'product' ? Number(data.product_id) : null,
        priority: Number.parseInt(data.priority, 10),
        starts_at: data.starts_at || null,
        expires_at: data.expires_at || null,
    })).put(route('admin.promotions.update', props.promotion.id));
};

const destroyPromotion = () => {
    router.delete(route('admin.promotions.destroy', props.promotion.id));
};
</script>

<template>
    <Head title="Editar promocao" />

    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar promocao
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

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel for="scope" value="Escopo" />
                        <select
                            id="scope"
                            v-model="form.scope"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option
                                v-for="option in scopeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="priority" value="Prioridade" />
                        <TextInput
                            id="priority"
                            v-model="form.priority"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>
                </div>

                <div v-if="form.scope === 'category'">
                    <InputLabel value="Categoria" />
                    <select
                        v-model="form.category_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="">Selecione</option>
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

                <div v-if="form.scope === 'brand'">
                    <InputLabel value="Marca" />
                    <select
                        v-model="form.brand_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="">Selecione</option>
                        <option
                            v-for="brand in brands"
                            :key="brand.id"
                            :value="brand.id"
                        >
                            {{ brand.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.brand_id" />
                </div>

                <div v-if="form.scope === 'product'">
                    <InputLabel value="Produto" />
                    <select
                        v-model="form.product_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required
                    >
                        <option value="">Selecione</option>
                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="product.id"
                        >
                            {{ product.name }}
                        </option>
                    </select>
                    <InputError
                        class="mt-2"
                        :message="form.errors.product_id"
                    />
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
                    <span class="text-sm text-gray-700">Promocao ativa</span>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="form.processing">
                            Salvar
                        </PrimaryButton>
                        <Link
                            :href="route('admin.promotions.index')"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Cancelar
                        </Link>
                    </div>
                    <button
                        type="button"
                        class="text-sm text-red-600 hover:text-red-800"
                        @click="destroyPromotion"
                    >
                        Excluir
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
