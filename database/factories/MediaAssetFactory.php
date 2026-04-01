<?php

namespace Database\Factories;

use App\MediaKind;
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
            'is_primary' => false,
            'position' => fake()->numberBetween(0, 10),
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
}
