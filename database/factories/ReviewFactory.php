<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
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
            'headline' => fake()->sentence(5),
            'body' => fake()->paragraphs(3, true),
            'contains_spoilers' => false,
            'status' => ReviewStatus::Pending,
            'moderated_by' => null,
            'moderated_at' => null,
            'published_at' => null,
            'edited_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::Published,
            'published_at' => now(),
        ]);
    }
}
