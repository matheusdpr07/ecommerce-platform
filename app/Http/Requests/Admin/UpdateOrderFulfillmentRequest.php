<?php

namespace App\Http\Requests\Admin;

use App\Enums\FulfillmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderFulfillmentRequest extends FormRequest
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
            'fulfillment_status' => [
                'required',
                Rule::in([
                    FulfillmentStatus::Preparing->value,
                    FulfillmentStatus::Shipped->value,
                    FulfillmentStatus::Delivered->value,
                ]),
            ],
            'tracking_code' => ['nullable', 'string', 'max:100'],
            'tracking_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'fulfillment_status.required' => 'Informe a nova etapa do pedido.',
            'fulfillment_status.in' => 'A etapa de entrega informada e invalida.',
            'tracking_code.max' => 'O codigo de rastreio deve ter no maximo 100 caracteres.',
            'tracking_url.url' => 'Informe um link de rastreio HTTP ou HTTPS valido.',
        ];
    }
}
