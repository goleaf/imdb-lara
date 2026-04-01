<?php

namespace Database\Factories;

use App\Enums\MediaKind;
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
        $kind = fake()->randomElement([
            MediaKind::Headshot,
            MediaKind::Gallery,
            MediaKind::Still,
        ]);
        $image = ImdbImageCatalog::personImage($kind, fake()->numberBetween(0, 7));

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
