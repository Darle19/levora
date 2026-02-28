<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StopSale>
 */
class StopSaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'date_from' => now()->subDays(5),
            'date_to' => now()->addDays(30),
            'reason' => 'Renovation',
            'is_active' => true,
        ];
    }
}
