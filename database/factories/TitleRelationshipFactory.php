<?php

namespace Database\Factories;

use App\Enums\TitleRelationshipType;
use App\Models\Title;
use App\Models\TitleRelationship;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TitleRelationship>
 */
class TitleRelationshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_title_id' => Title::factory(),
            'to_title_id' => Title::factory(),
            'relationship_type' => fake()->randomElement(TitleRelationshipType::cases()),
            'weight' => fake()->numberBetween(1, 10),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
