<?php

namespace Database\Factories;

use App\Models\Award;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Award>
 */
class AwardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Awards';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'country_code' => fake()->countryCode(),
            'meta_title' => null,
            'meta_description' => null,
            'is_published' => true,
        ];
    }
}
