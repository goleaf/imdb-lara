<?php

namespace Database\Factories;

use App\ReportReason;
use App\ReportStatus;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
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
            'reportable_type' => Review::class,
            'reportable_id' => Review::factory(),
            'reason' => ReportReason::Spoiler,
            'details' => fake()->sentence(),
            'status' => ReportStatus::Open,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportStatus::Open,
        ]);
    }
}
