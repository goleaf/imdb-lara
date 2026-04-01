<?php

namespace Database\Factories;

use App\Models\Title;
use App\Models\TitleTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TitleTranslation>
 */
class TitleTranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'title_id' => Title::factory(),
            'locale' => fake()->randomElement(['en', 'lt', 'fr', 'es']),
            'localized_title' => $title,
            'localized_slug' => Str::slug($title),
            'localized_plot_outline' => fake()->sentence(),
            'localized_synopsis' => fake()->paragraphs(2, true),
            'localized_tagline' => fake()->optional()->sentence(3),
            'meta_title' => null,
            'meta_description' => null,
        ];
    }
}
