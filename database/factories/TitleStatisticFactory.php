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
        $ratingDistribution = TitleStatistic::normalizeRatingDistribution(
            collect(range(10, 1))
                ->mapWithKeys(fn (int $score): array => [
                    (string) $score => fake()->boolean(35) ? fake()->numberBetween(0, 40) : 0,
                ])
                ->all(),
        );
        $ratingCount = array_sum($ratingDistribution);
        $weightedScoreTotal = collect($ratingDistribution)->reduce(
            fn (int $carry, int $count, string $score): int => $carry + ((int) $score * $count),
            0,
        );

        return [
            'title_id' => Title::factory(),
            'rating_count' => $ratingCount,
            'average_rating' => $ratingCount > 0
                ? round($weightedScoreTotal / $ratingCount, 2)
                : 0,
            'rating_distribution' => $ratingDistribution,
            'review_count' => fake()->numberBetween(0, 200),
            'watchlist_count' => fake()->numberBetween(0, 1000),
            'episodes_count' => fake()->numberBetween(0, 40),
            'awards_nominated_count' => fake()->numberBetween(0, 15),
            'awards_won_count' => fake()->numberBetween(0, 8),
        ];
    }
}
