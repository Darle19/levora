<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => fake()->city(),
            'name_ru' => fake()->city(),
            'name_uz' => fake()->city(),
            'country_id' => Country::factory(),
            'is_active' => true,
            'order' => fake()->numberBetween(1, 100),
        ];
    }
}
