<?php

namespace Database\Factories;

use App\ListVisibility;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserList>
 */
class UserListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'visibility' => ListVisibility::Private,
            'is_watchlist' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (): array => [
            'visibility' => ListVisibility::Public,
        ]);
    }

    public function watchlist(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Watchlist',
            'slug' => 'watchlist',
            'visibility' => ListVisibility::Private,
            'is_watchlist' => true,
        ]);
    }
}
