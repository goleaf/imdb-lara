<?php

namespace Database\Factories;

use App\Models\AwardCategory;
use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Company;
use App\Models\Episode;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AwardNomination>
 */
class AwardNominationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'award_event_id' => AwardEvent::factory(),
            'award_category_id' => AwardCategory::factory(),
            'title_id' => Title::factory(),
            'person_id' => null,
            'company_id' => null,
            'episode_id' => null,
            'credited_name' => null,
            'details' => fake()->optional()->sentence(),
            'is_winner' => false,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function winner(): static
    {
        return $this->state(fn (): array => [
            'is_winner' => true,
        ]);
    }

    public function forPerson(): static
    {
        return $this->state(fn (): array => [
            'title_id' => null,
            'person_id' => Person::factory(),
        ]);
    }

    public function forCompany(): static
    {
        return $this->state(fn (): array => [
            'title_id' => null,
            'company_id' => Company::factory(),
        ]);
    }

    public function forEpisode(): static
    {
        return $this->state(fn (): array => [
            'episode_id' => Episode::factory(),
        ]);
    }
}
