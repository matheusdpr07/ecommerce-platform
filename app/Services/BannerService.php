<?php

namespace App\Services;

use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BannerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image): Banner
    {
        unset($data['image'], $data['remove_image']);

        if ($image) {
            $data['image_path'] = $image->store('banners', 'public');
        }

        return Banner::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Banner $banner, array $data, ?UploadedFile $image): Banner
    {
        $removeImage = (bool) ($data['remove_image'] ?? false);
        $previousImage = $banner->image_path;

        unset($data['image'], $data['remove_image']);

        if ($image) {
            $data['image_path'] = $image->store('banners', 'public');
        } elseif ($removeImage) {
            $data['image_path'] = null;
        }

        $banner->update($data);

        if (($image || $removeImage) && $previousImage) {
            Storage::disk('public')->delete($previousImage);
        }

        return $banner->refresh();
    }

    public function delete(Banner $banner): void
    {
        $banner->delete();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function activeForStorefront(): Collection
    {
        return Banner::query()
            ->active()
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'eyebrow' => $banner->eyebrow,
                'description' => $banner->description,
                'image_url' => $banner->imageUrl(),
                'image_alt' => $banner->image_alt,
                'cta_label' => $banner->cta_label,
                'cta_url' => $banner->cta_url,
                'theme' => $banner->theme,
                'placement' => $banner->placement,
            ]);
    }
}
