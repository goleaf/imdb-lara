<?php

namespace Database\Factories;

use App\Enums\MediaKind;
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
        $kind = fake()->randomElement([
            MediaKind::Poster,
            MediaKind::Backdrop,
            MediaKind::Gallery,
            MediaKind::Still,
        ]);
        $image = ImdbImageCatalog::titleImage($kind, fake()->numberBetween(0, 7));

        return [
            'kind' => $kind,
            ...$image,
            'alt_text' => fake()->sentence(4),
            'caption' => fake()->optional()->sentence(),
            'language' => fake()->randomElement(['en', 'lt', 'fr']),
            'duration_seconds' => null,
            'metadata' => null,
            'is_primary' => true,
            'position' => 0,
            'published_at' => now(),
        ];
    }
}
