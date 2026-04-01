<?php

namespace Database\Factories;

use App\Models\Award;
use App\Models\AwardCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AwardCategory>
 */
class AwardCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Best Picture', 'Best Actor', 'Best Episode', 'Best Screenplay']);

        return [
            'award_id' => Award::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'recipient_scope' => fake()->randomElement(['title', 'person', 'episode', 'mixed']),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
