<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventorySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'low_stock_threshold.required' => 'Informe o limite de estoque baixo.',
            'low_stock_threshold.integer' => 'O limite deve ser um numero inteiro.',
            'low_stock_threshold.min' => 'O limite nao pode ser negativo.',
            'low_stock_threshold.max' => 'O limite informado e muito alto.',
        ];
    }
}
