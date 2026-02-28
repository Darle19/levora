<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TourType>
 */
class TourTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => 'Beach Tour',
            'name_ru' => 'Пляжный тур',
            'name_uz' => 'Plyaj tur',
            'is_active' => true,
        ];
    }
}
