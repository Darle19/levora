<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . Str::ulid(),
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'currency_id' => Currency::factory(),
            'status' => 'pending',
            'total_price' => fake()->randomFloat(2, 500, 10000),
        ];
    }
}
