<?php

namespace Database\Factories;

use App\Enums\WatchState;
use App\Models\ListItem;
use App\Models\Title;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListItem>
 */
class ListItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_list_id' => UserList::factory(),
            'title_id' => Title::factory(),
            'notes' => fake()->optional()->sentence(),
            'position' => fake()->numberBetween(1, 20),
            'watch_state' => WatchState::Planned,
            'started_at' => null,
            'watched_at' => null,
            'rewatch_count' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'watch_state' => WatchState::Completed,
            'started_at' => now()->subDays(7),
            'watched_at' => now(),
            'rewatch_count' => 1,
        ]);
    }
}
