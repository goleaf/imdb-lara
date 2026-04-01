<?php

namespace Database\Factories;

use App\ContributionAction;
use App\ContributionStatus;
use App\Models\Contribution;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribution>
 */
class ContributionFactory extends Factory
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
            'contributable_type' => Title::class,
            'contributable_id' => Title::factory(),
            'action' => fake()->randomElement(ContributionAction::cases()),
            'status' => ContributionStatus::Submitted,
            'payload' => [
                'field' => 'plot_outline',
                'value' => fake()->sentence(),
            ],
            'notes' => fake()->optional()->sentence(),
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }
}
