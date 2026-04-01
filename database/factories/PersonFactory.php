<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 9999),
            'biography' => fake()->paragraphs(2, true),
            'known_for_department' => fake()->randomElement(['Acting', 'Directing', 'Writing', 'Production']),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-20 years'),
            'death_date' => null,
            'birth_place' => sprintf('%s, %s', fake()->city(), fake()->country()),
            'popularity_rank' => fake()->numberBetween(1, 500),
            'is_published' => true,
        ];
    }
}
