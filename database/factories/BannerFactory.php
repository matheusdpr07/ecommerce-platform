<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'eyebrow' => fake()->words(3, true),
            'description' => fake()->sentence(12),
            'image_path' => null,
            'image_alt' => null,
            'cta_label' => 'Explorar seleção',
            'cta_url' => '/',
            'theme' => 'paper',
            'placement' => 'hero',
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function editorial(): static
    {
        return $this->state(fn () => ['placement' => 'editorial']);
    }
}
