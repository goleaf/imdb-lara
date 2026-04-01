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
        return [
            'kind' => MediaKind::Gallery,
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
            'is_primary' => false,
            'position' => fake()->numberBetween(0, 10),
            'published_at' => now(),
        ];
    }

    public function poster(): static
    {
        return $this->state(fn (): array => [
            'kind' => MediaKind::Poster,
            'is_primary' => true,
            'position' => 0,
        ]);
    }

    public function backdrop(): static
    {
        return $this->state(fn (): array => [
            'kind' => MediaKind::Backdrop,
        ]);
    }

    public function headshot(): static
    {
        return $this->state(fn (): array => [
            'kind' => MediaKind::Headshot,
            'width' => 900,
            'height' => 1200,
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
