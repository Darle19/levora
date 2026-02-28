<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        $code = 'T' . strtoupper(fake()->unique()->lexify('??'));

        return [
            'name_en' => $code . ' Currency',
            'name_ru' => $code . ' Валюта',
            'name_uz' => $code . ' Valyuta',
            'code' => $code,
            'symbol' => fake()->randomElement(['$', '€', '£', '¥', '₽', '₺']),
            'is_active' => true,
        ];
    }
}
