<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Banner::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:100', 'required_with:cta_url'],
            'cta_url' => [
                'nullable',
                'string',
                'max:2048',
                'required_with:cta_label',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || $value === '') {
                        return;
                    }

                    $isInternal = str_starts_with($value, '/') || str_starts_with($value, '#');
                    $scheme = parse_url($value, PHP_URL_SCHEME);

                    if (! $isInternal && ! in_array($scheme, ['http', 'https'], true)) {
                        $fail('Informe um caminho interno ou uma URL HTTP segura.');
                    }
                },
            ],
            'theme' => ['required', Rule::in(['paper', 'ink', 'accent'])],
            'placement' => ['required', Rule::in(['hero', 'editorial'])],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título do banner.',
            'image.image' => 'Envie um arquivo de imagem válido.',
            'image.max' => 'A imagem deve ter no máximo 4 MB.',
            'cta_label.required_with' => 'Informe o texto do botão.',
            'cta_url.required_with' => 'Informe o destino do botão.',
            'ends_at.after' => 'O término deve ser posterior ao início.',
        ];
    }
}
