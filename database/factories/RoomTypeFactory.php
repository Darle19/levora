<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoomType>
 */
class RoomTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => 'Standard Double',
            'name_ru' => 'Стандарт двухместный',
            'name_uz' => 'Standart ikki kishilik',
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'max_adults' => 2,
            'max_children' => 1,
            'is_active' => true,
        ];
    }
}
