<?php

namespace Database\Factories;

use App\Models\Title;
use App\Models\TitleStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TitleStatistic>
 */
class TitleStatisticFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_id' => Title::factory(),
            'rating_count' => fake()->numberBetween(1, 5000),
            'average_rating' => fake()->randomFloat(2, 5.5, 9.8),
            'review_count' => fake()->numberBetween(0, 200),
            'watchlist_count' => fake()->numberBetween(0, 1000),
            'episodes_count' => fake()->numberBetween(0, 40),
            'awards_nominated_count' => fake()->numberBetween(0, 15),
            'awards_won_count' => fake()->numberBetween(0, 8),
        ];
    }
}
