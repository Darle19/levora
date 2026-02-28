<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdditionalService>
 */
class AdditionalServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'name_en' => 'Transfer Service',
            'name_ru' => 'Трансфер',
            'name_uz' => 'Transfer xizmati',
            'service_type' => fake()->randomElement(['transfer', 'excursion', 'insurance', 'other']),
            'price' => fake()->randomFloat(2, 10, 200),
            'currency_id' => Currency::factory(),
            'is_per_person' => true,
            'is_active' => true,
        ];
    }
}
