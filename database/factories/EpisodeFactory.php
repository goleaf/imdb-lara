<?php

namespace Database\Factories;

use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_id' => Title::factory()->episode(),
            'series_id' => Title::factory()->series(),
            'season_id' => Season::factory(),
            'season_number' => 1,
            'episode_number' => fake()->numberBetween(1, 12),
            'absolute_number' => fake()->numberBetween(1, 100),
            'production_code' => strtoupper(fake()->bothify('??###')),
            'aired_at' => fake()->dateTimeBetween('-10 years', 'now'),
        ];
    }
}
