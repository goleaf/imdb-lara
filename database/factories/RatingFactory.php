<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title_id' => Title::factory(),
            'score' => fake()->numberBetween(1, 10),
        ];
    }
}
