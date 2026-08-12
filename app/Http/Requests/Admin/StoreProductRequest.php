<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge($this->productRules(), $this->variantRules(), $this->imageRules());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do produto.',
            'slug.unique' => 'Este slug ja esta em uso.',
            'category_id.required' => 'Selecione uma categoria.',
            'category_id.exists' => 'A categoria informada nao existe.',
            'brand_id.exists' => 'A marca informada nao existe.',
            'variants.required' => 'Informe ao menos uma variacao.',
            'variants.min' => 'Informe ao menos uma variacao.',
            'variants.*.sku.required' => 'Informe o SKU de cada variacao.',
            'variants.*.sku.unique' => 'Este SKU ja esta em uso.',
            'variants.*.name.required' => 'Informe o nome de cada variacao.',
            'variants.*.price_cents.required' => 'Informe o preco de cada variacao.',
            'variants.*.price_cents.min' => 'O preco deve ser zero ou maior.',
            'variants.*.stock_quantity.required' => 'Informe o estoque de cada variacao.',
            'variants.*.stock_quantity.min' => 'O estoque nao pode ser negativo.',
            'images.*.image' => 'Envie apenas arquivos de imagem validos.',
            'images.*.max' => 'Cada imagem deve ter no maximo 2 MB.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'is_active' => ['required', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function variantRules(): array
    {
        return [
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.price_cents' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_price_cents' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['required', 'boolean'],
            'variants.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function imageRules(): array
    {
        return [
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
        ];
    }
}
