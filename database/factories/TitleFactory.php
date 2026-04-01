<?php

namespace Database\Factories;

use App\Models\Title;
use App\TitleType;
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
            'sort_title' => $name,
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
            'canonical_title_id' => null,
            'meta_title' => null,
            'meta_description' => null,
            'search_keywords' => implode(', ', fake()->words(4)),
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

    public function miniSeries(): static
    {
        return $this->state(fn (): array => [
            'title_type' => TitleType::MiniSeries,
            'runtime_minutes' => fake()->numberBetween(35, 70),
        ]);
    }

    public function documentary(): static
    {
        return $this->state(fn (): array => [
            'title_type' => TitleType::Documentary,
        ]);
    }

    public function short(): static
    {
        return $this->state(fn (): array => [
            'title_type' => TitleType::Short,
            'runtime_minutes' => fake()->numberBetween(8, 35),
        ]);
    }

    public function special(): static
    {
        return $this->state(fn (): array => [
            'title_type' => TitleType::Special,
            'runtime_minutes' => fake()->numberBetween(40, 100),
        ]);
    }

    public function episode(): static
    {
        return $this->state(fn (): array => [
            'title_type' => TitleType::Episode,
            'runtime_minutes' => fake()->numberBetween(24, 62),
        ]);
    }
}
