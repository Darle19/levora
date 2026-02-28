<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'currency_id' => Currency::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'payment_method' => fake()->randomElement(['bank_transfer', 'cash']),
            'payment_date' => now(),
            'status' => 'completed',
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }
}
