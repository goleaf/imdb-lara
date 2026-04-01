<?php

namespace Database\Factories;

use App\Models\ModerationAction;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModerationAction>
 */
class ModerationActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'moderator_id' => User::factory()->moderator(),
            'report_id' => null,
            'actionable_type' => Review::class,
            'actionable_id' => Review::factory(),
            'action' => fake()->randomElement(['approve', 'reject', 'feature']),
            'notes' => fake()->optional()->sentence(),
            'metadata' => null,
        ];
    }
}
