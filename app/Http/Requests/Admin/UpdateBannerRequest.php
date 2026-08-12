<?php

namespace App\Http\Requests\Admin;

use App\Models\Banner;

class UpdateBannerRequest extends StoreBannerRequest
{
    public function authorize(): bool
    {
        /** @var Banner $banner */
        $banner = $this->route('banner');

        return $this->user()?->can('update', $banner) ?? false;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remove_image' => ['nullable', 'boolean'],
        ];
    }
}
