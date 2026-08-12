<?php

namespace App\Http\Requests\Store;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Escolha uma nota de 1 a 5.',
            'rating.between' => 'A nota deve estar entre 1 e 5.',
            'body.required' => 'Conte como foi a sua experiência.',
            'body.min' => 'Escreva ao menos 10 caracteres.',
            'body.max' => 'A avaliação deve ter no máximo 2.000 caracteres.',
        ];
    }
}
