<?php

namespace App\Http\Requests\Store;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id'),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'Selecione uma variacao valida.',
            'product_variant_id.exists' => 'A variacao informada nao existe.',
            'quantity.required' => 'Informe a quantidade.',
            'quantity.min' => 'A quantidade minima e 1.',
        ];
    }
}
