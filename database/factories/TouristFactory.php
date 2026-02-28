<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tourist>
 */
class TouristFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'title' => fake()->randomElement(['MR', 'MRS']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'nationality' => 'UZ',
            'passport_number' => fake()->numerify('########'),
            'passport_expiry' => fake()->dateTimeBetween('+1 year', '+5 years'),
        ];
    }
}
