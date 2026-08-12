<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
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
            'operation' => ['required', Rule::in(['set', 'restock'])],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'operation.required' => 'Escolha o tipo de movimentacao.',
            'operation.in' => 'O tipo de movimentacao e invalido.',
            'quantity.required' => 'Informe a quantidade.',
            'quantity.integer' => 'A quantidade deve ser um numero inteiro.',
            'quantity.min' => 'A quantidade nao pode ser negativa.',
            'quantity.max' => 'A quantidade informada e muito alta.',
        ];
    }
}
