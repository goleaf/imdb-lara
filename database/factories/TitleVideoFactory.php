<?php

namespace Database\Factories;

use App\Enums\MediaKind;
use App\Models\TitleVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TitleVideo>
 */
class TitleVideoFactory extends Factory
{
    protected $model = TitleVideo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => fake()->randomElement([
                MediaKind::Trailer,
                MediaKind::Clip,
                MediaKind::Featurette,
            ]),
            'url' => sprintf('https://videos.example.test/%s', fake()->uuid()),
            'alt_text' => fake()->sentence(4),
            'caption' => fake()->optional()->sentence(),
            'width' => 1920,
            'height' => 1080,
            'provider' => 'internal',
            'provider_key' => fake()->uuid(),
            'language' => fake()->randomElement(['en', 'lt', 'fr']),
            'duration_seconds' => fake()->numberBetween(45, 240),
            'metadata' => null,
            'is_primary' => false,
            'position' => fake()->numberBetween(0, 10),
            'published_at' => now(),
        ];
    }
}
