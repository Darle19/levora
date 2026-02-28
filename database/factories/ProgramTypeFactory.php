<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgramType>
 */
class ProgramTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => 'Standard',
            'name_ru' => 'Стандарт',
            'name_uz' => 'Standart',
            'is_active' => true,
        ];
    }
}
