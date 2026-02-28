<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => fake()->country(),
            'name_ru' => fake()->country(),
            'name_uz' => fake()->country(),
            'code' => fake()->unique()->countryCode(),
            'is_active' => true,
            'order' => fake()->numberBetween(1, 100),
        ];
    }
}
