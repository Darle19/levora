<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Hotel;
use App\Models\MealType;
use App\Models\ProgramType;
use App\Models\Resort;
use App\Models\TourType;
use App\Models\TransportType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tour>
 */
class TourFactory extends Factory
{
    public function definition(): array
    {
        $dateFrom = fake()->dateTimeBetween('+1 week', '+3 months');
        $nights = fake()->numberBetween(3, 14);

        return [
            'tour_type_id' => TourType::factory(),
            'program_type_id' => ProgramType::factory(),
            'country_id' => Country::factory(),
            'resort_id' => Resort::factory(),
            'hotel_id' => Hotel::factory(),
            'transport_type_id' => TransportType::factory(),
            'departure_city_id' => City::factory(),
            'currency_id' => Currency::factory(),
            'meal_type_id' => MealType::factory(),
            'nights' => $nights,
            'price' => fake()->randomFloat(2, 200, 5000),
            'date_from' => $dateFrom,
            'date_to' => (clone $dateFrom)->modify("+{$nights} days"),
            'adults' => fake()->numberBetween(1, 4),
            'children' => fake()->numberBetween(0, 2),
            'is_available' => true,
            'is_hot' => false,
            'instant_confirmation' => false,
            'no_stop_sale' => false,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn() => ['is_available' => false]);
    }

    public function past(): static
    {
        return $this->state(fn() => [
            'date_from' => fake()->dateTimeBetween('-2 months', '-1 day'),
            'date_to' => fake()->dateTimeBetween('-1 month', 'yesterday'),
        ]);
    }

    public function hot(): static
    {
        return $this->state(fn() => ['is_hot' => true]);
    }
}
