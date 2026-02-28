<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resort>
 */
class ResortFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => fake()->city() . ' Resort',
            'name_ru' => fake()->city() . ' Resort',
            'name_uz' => fake()->city() . ' Resort',
            'country_id' => Country::factory(),
            'is_active' => true,
            'order' => fake()->numberBetween(1, 100),
        ];
    }
}
