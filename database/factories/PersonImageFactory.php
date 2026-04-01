<?php

namespace Database\Factories;

use App\MediaKind;
use App\Models\PersonImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonImage>
 */
class PersonImageFactory extends Factory
{
    protected $model = PersonImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => fake()->randomElement([
                MediaKind::Headshot,
                MediaKind::Gallery,
                MediaKind::Still,
            ]),
            'url' => fake()->imageUrl(900, 1200),
            'alt_text' => fake()->sentence(4),
            'caption' => fake()->optional()->sentence(),
            'width' => 900,
            'height' => 1200,
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
