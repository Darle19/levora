<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Airport>
 */
class AirportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => fake()->city() . ' Airport',
            'name_ru' => fake()->city() . ' Airport',
            'name_uz' => fake()->city() . ' Airport',
            'city_id' => City::factory(),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'is_active' => true,
        ];
    }
}
