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
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(30),
            'reason' => 'Renovation',
            'is_active' => true,
        ];
    }
}
