<?php

namespace Database\Factories;

use App\CompanyKind;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'kind' => fake()->randomElement(CompanyKind::cases()),
            'country_code' => fake()->countryCode(),
            'description' => fake()->sentence(),
            'is_published' => true,
        ];
    }
}
