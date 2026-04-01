<?php

namespace Database\Factories;

use App\TitleType;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Title>
 */
class TitleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'name' => $name,
            'original_name' => $name,
            'slug' => Str::slug($name),
            'title_type' => TitleType::Movie,
            'release_year' => fake()->numberBetween(1980, 2025),
            'end_year' => null,
            'release_date' => fake()->dateTimeBetween('-40 years', 'now'),
            'runtime_minutes' => fake()->numberBetween(80, 180),
            'age_rating' => fake()->randomElement(['PG', 'PG-13', 'R', 'TV-14']),
            'plot_outline' => fake()->sentence(),
            'synopsis' => fake()->paragraphs(3, true),
            'tagline' => fake()->sentence(4),
            'origin_country' => fake()->countryCode(),
            'original_language' => fake()->randomElement(['en', 'lt', 'fr', 'es']),
            'popularity_rank' => fake()->numberBetween(1, 500),
            'is_published' => true,
        ];
    }

    public function movie(): static
    {
        return $this->state(fn (): array => [
            'title_type' => TitleType::Movie,
        ]);
    }

    public function series(): static
    {
        return $this->state(fn (): array => [
            'title_type' => TitleType::Series,
            'runtime_minutes' => fake()->numberBetween(25, 60),
        ]);
    }

    public function documentary(): static
    {
        return $this->state(fn (): array => [
            'title_type' => TitleType::Documentary,
        ]);
    }
}
