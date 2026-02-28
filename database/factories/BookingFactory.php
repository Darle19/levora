<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'bookable_type' => Tour::class,
            'bookable_id' => Tour::factory(),
            'currency_id' => Currency::factory(),
            'status' => 'pending',
            'price' => fake()->randomFloat(2, 500, 10000),
            'date' => fake()->dateTimeBetween('+1 week', '+3 months'),
        ];
    }
}
