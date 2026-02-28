<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealType>
 */
class MealTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => fake()->words(2, true),
            'name_ru' => fake()->words(2, true),
            'name_uz' => fake()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'is_active' => true,
        ];
    }
}
