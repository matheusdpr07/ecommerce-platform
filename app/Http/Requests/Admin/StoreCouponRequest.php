<?php

namespace App\Http\Requests\Admin;

use App\Enums\DiscountType;
use App\Models\Coupon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Coupon::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('coupons', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(DiscountType::class)],
            'value' => ['required', 'integer', 'min:1'],
            'min_order_cents' => ['nullable', 'integer', 'min:0'],
            'max_discount_cents' => ['nullable', 'integer', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
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
            'code.required' => 'Informe o codigo do cupom.',
            'code.unique' => 'Este codigo ja esta em uso.',
            'name.required' => 'Informe o nome interno do cupom.',
            'value.required' => 'Informe o valor do desconto.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = DiscountType::tryFrom((string) $this->input('type'));
            $value = (int) $this->input('value');

            if ($type === DiscountType::Percentage && ($value < 1 || $value > 100)) {
                $validator->errors()->add('value', 'Informe um percentual entre 1 e 100.');
            }
        });
    }
}
