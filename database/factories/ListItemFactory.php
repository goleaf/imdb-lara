<?php

namespace Database\Factories;

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
        ];
    }
}
