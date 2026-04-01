<?php

namespace Database\Factories;

use App\Models\Award;
use App\Models\AwardEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AwardEvent>
 */
class AwardEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2000, 2026);
        $name = $year.' Ceremony';

        return [
            'award_id' => Award::factory(),
            'name' => $name,
            'slug' => Str::slug(fake()->unique()->sentence(3)),
            'year' => $year,
            'edition' => fake()->optional()->numerify('#th'),
            'event_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'location' => fake()->city(),
            'details' => fake()->optional()->sentence(),
        ];
    }
}
