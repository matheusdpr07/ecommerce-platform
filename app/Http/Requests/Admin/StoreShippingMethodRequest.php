<?php

namespace App\Http\Requests\Admin;

use App\Models\ShippingMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ShippingMethod::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'free_above_cents' => ['nullable', 'integer', 'min:0'],
            'min_order_cents' => ['nullable', 'integer', 'min:0'],
            'max_order_cents' => ['nullable', 'integer', 'min:0'],
            'estimated_days_min' => ['nullable', 'integer', 'min:1'],
            'estimated_days_max' => ['nullable', 'integer', 'min:1', 'gte:estimated_days_min'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do metodo de frete.',
            'price_cents.required' => 'Informe o valor do frete.',
        ];
    }
}
