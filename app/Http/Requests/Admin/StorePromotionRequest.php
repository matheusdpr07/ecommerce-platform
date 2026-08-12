<?php

namespace App\Http\Requests\Admin;

use App\Enums\DiscountType;
use App\Enums\PromotionScope;
use App\Models\Promotion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Promotion::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(DiscountType::class)],
            'value' => ['required', 'integer', 'min:1'],
            'scope' => ['required', new Enum(PromotionScope::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'priority' => ['required', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da promocao.',
            'value.required' => 'Informe o valor do desconto.',
            'scope.required' => 'Selecione o escopo da promocao.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = DiscountType::tryFrom((string) $this->input('type'));
            $value = (int) $this->input('value');
            $scope = PromotionScope::tryFrom((string) $this->input('scope'));

            if ($type === DiscountType::Percentage && ($value < 1 || $value > 100)) {
                $validator->errors()->add('value', 'Informe um percentual entre 1 e 100.');
            }

            if ($scope === PromotionScope::Category && blank($this->input('category_id'))) {
                $validator->errors()->add('category_id', 'Selecione uma categoria.');
            }

            if ($scope === PromotionScope::Brand && blank($this->input('brand_id'))) {
                $validator->errors()->add('brand_id', 'Selecione uma marca.');
            }

            if ($scope === PromotionScope::Product && blank($this->input('product_id'))) {
                $validator->errors()->add('product_id', 'Selecione um produto.');
            }
        });
    }
}
