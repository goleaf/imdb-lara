<?php

namespace Database\Factories;

use App\Models\Credit;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Credit>
 */
class CreditFactory extends Factory
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
            'person_id' => Person::factory(),
            'department' => fake()->randomElement(['Cast', 'Directing', 'Writing']),
            'job' => fake()->randomElement(['Actor', 'Director', 'Writer']),
            'character_name' => fake()->optional()->name(),
            'billing_order' => fake()->numberBetween(1, 10),
            'is_principal' => fake()->boolean(35),
        ];
    }
}
