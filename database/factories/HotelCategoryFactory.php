<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HotelCategory>
 */
class HotelCategoryFactory extends Factory
{
    public function definition(): array
    {
        $stars = fake()->numberBetween(1, 5);

        return [
            'name' => $stars . ' Star',
            'stars' => $stars,
            'is_active' => true,
        ];
    }
}
