<?php

namespace Database\Factories;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flight>
 */
class FlightFactory extends Factory
{
    public function definition(): array
    {
        $departure = fake()->dateTimeBetween('+1 week', '+3 months');

        return [
            'airline_id' => Airline::factory(),
            'from_airport_id' => Airport::factory(),
            'to_airport_id' => Airport::factory(),
            'currency_id' => Currency::factory(),
            'flight_number' => strtoupper(fake()->lexify('??')) . fake()->numerify('###'),
            'departure_date' => $departure,
            'departure_time' => fake()->time('H:i'),
            'arrival_date' => $departure,
            'arrival_time' => fake()->time('H:i'),
            'price_adult' => fake()->randomFloat(2, 100, 1000),
            'price_child' => fake()->randomFloat(2, 50, 500),
            'price_infant' => fake()->randomFloat(2, 10, 100),
            'available_seats' => fake()->numberBetween(10, 200),
            'class_type' => 'economy',
            'is_active' => true,
        ];
    }
}
