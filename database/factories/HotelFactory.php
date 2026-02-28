<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\HotelCategory;
use App\Models\Resort;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hotel>
 */
class HotelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Hotel',
            'name_en' => fake()->company() . ' Hotel',
            'name_ru' => fake()->company() . ' Hotel',
            'resort_id' => Resort::factory(),
            'hotel_category_id' => HotelCategory::factory(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'is_active' => true,
            'rating' => fake()->randomFloat(1, 3, 5),
            'price_per_person' => fake()->randomFloat(2, 50, 500),
            'currency_id' => Currency::factory(),
        ];
    }
}
