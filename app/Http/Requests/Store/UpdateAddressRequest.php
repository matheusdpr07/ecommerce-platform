<?php

namespace App\Http\Requests\Store;

use App\Models\Address;
use App\Support\BrazilianStates;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Address $address */
        $address = $this->route('address');

        return $this->user()?->can('update', $address) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2', Rule::in(BrazilianStates::codes())],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'Informe um nome para o endereco.',
            'recipient_name.required' => 'Informe o nome do destinatario.',
            'postal_code.required' => 'Informe o CEP.',
            'postal_code.regex' => 'Informe um CEP valido.',
            'state.in' => 'Selecione um estado valido.',
        ];
    }
}
