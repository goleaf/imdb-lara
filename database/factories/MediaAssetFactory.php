<?php

namespace Database\Factories;

use App\Enums\MediaKind;
use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $galleryImage = ImdbImageCatalog::titleGallery(fake()->numberBetween(0, 7));

        return [
            'kind' => MediaKind::Gallery,
            ...$galleryImage,
            'alt_text' => fake()->sentence(4),
            'caption' => fake()->optional()->sentence(),
            'language' => fake()->randomElement(['en', 'lt', 'fr']),
            'duration_seconds' => null,
            'metadata' => null,
            'is_primary' => false,
            'position' => fake()->numberBetween(0, 10),
            'published_at' => now(),
        ];
    }

    public function poster(): static
    {
        $posterImage = ImdbImageCatalog::titlePoster(fake()->numberBetween(0, 7));

        return $this->state(fn (): array => [
            ...$posterImage,
            'kind' => MediaKind::Poster,
            'is_primary' => true,
            'position' => 0,
        ]);
    }

    public function backdrop(): static
    {
        $backdropImage = ImdbImageCatalog::titleBackdrop(fake()->numberBetween(0, 7));

        return $this->state(fn (): array => [
            ...$backdropImage,
            'kind' => MediaKind::Backdrop,
        ]);
    }

    public function headshot(): static
    {
        $headshotImage = ImdbImageCatalog::personHeadshot(fake()->numberBetween(0, 7));

        return $this->state(fn (): array => [
            ...$headshotImage,
            'kind' => MediaKind::Headshot,
        ]);
    }

    public function trailer(): static
    {
        return $this->state(fn (): array => [
            'kind' => MediaKind::Trailer,
            'url' => sprintf('https://videos.example.test/%s', fake()->uuid()),
            'provider' => 'internal',
            'provider_key' => fake()->uuid(),
            'duration_seconds' => fake()->numberBetween(60, 180),
        ]);
    }
}
