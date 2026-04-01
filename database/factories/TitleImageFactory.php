<?php

namespace Database\Factories;

use App\MediaKind;
use App\Models\TitleImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TitleImage>
 */
class TitleImageFactory extends Factory
{
    protected $model = TitleImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => fake()->randomElement([
                MediaKind::Poster,
                MediaKind::Backdrop,
                MediaKind::Gallery,
                MediaKind::Still,
            ]),
            'url' => fake()->imageUrl(1200, 1800),
            'alt_text' => fake()->sentence(4),
            'caption' => fake()->optional()->sentence(),
            'width' => 1200,
            'height' => 1800,
            'provider' => null,
            'provider_key' => null,
            'language' => fake()->randomElement(['en', 'lt', 'fr']),
            'duration_seconds' => null,
            'metadata' => null,
            'is_primary' => true,
            'position' => 0,
            'published_at' => now(),
        ];
    }
}
