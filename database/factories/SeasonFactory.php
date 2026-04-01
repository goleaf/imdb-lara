<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->numberBetween(1, 6);
        $name = 'Season '.$number;

        return [
            'series_id' => Title::factory()->series(),
            'name' => $name,
            'slug' => Str::slug(fake()->unique()->sentence(3)),
            'season_number' => $number,
            'summary' => fake()->sentence(),
            'release_year' => fake()->numberBetween(2000, 2026),
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
