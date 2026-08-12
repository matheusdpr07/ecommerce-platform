<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Product $product */
        $product = $this->route('product');

        return $this->user()?->can('update', $product) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return array_merge(
            $this->productRules($product),
            $this->variantRules($product),
            $this->imageRules(),
        );
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
            'variants.*.id.exists' => 'Variacao invalida para este produto.',
            'variants.*.sku.required' => 'Informe o SKU de cada variacao.',
            'variants.*.sku.unique' => 'Este SKU ja esta em uso.',
            'variants.*.name.required' => 'Informe o nome de cada variacao.',
            'variants.*.price_cents.required' => 'Informe o preco de cada variacao.',
            'variants.*.price_cents.min' => 'O preco deve ser zero ou maior.',
            'variants.*.stock_quantity.required' => 'Informe o estoque de cada variacao.',
            'variants.*.stock_quantity.min' => 'O estoque nao pode ser negativo.',
            'images.*.image' => 'Envie apenas arquivos de imagem validos.',
            'images.*.max' => 'Cada imagem deve ter no maximo 2 MB.',
            'remove_image_ids.*.exists' => 'Imagem invalida para este produto.',
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function productRules(Product $product): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('products', 'slug')->ignore($product->id),
            ],
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
    protected function variantRules(Product $product): array
    {
        return [
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $product->id),
            ],
            'variants.*.sku' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $index = (int) explode('.', $attribute)[1];
                    $variantId = $this->input("variants.{$index}.id");

                    $exists = ProductVariant::query()
                        ->where('sku', $value)
                        ->when($variantId, fn ($query) => $query->where('id', '!=', $variantId))
                        ->exists();

                    if ($exists) {
                        $fail('Este SKU ja esta em uso.');
                    }
                },
            ],
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
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => [
                'integer',
                Rule::exists('product_images', 'id')->where('product_id', $this->route('product')->id),
            ],
        ];
    }
}
