<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\PersonProfession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonProfession>
 */
class PersonProfessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'department' => fake()->randomElement(['Cast', 'Directing', 'Writing', 'Production']),
            'profession' => fake()->randomElement(['Actor', 'Director', 'Writer', 'Producer']),
            'is_primary' => false,
            'sort_order' => fake()->numberBetween(0, 4),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => [
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }
}
