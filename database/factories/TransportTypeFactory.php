<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransportType>
 */
class TransportTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => 'Flight',
            'name_ru' => 'Авиа',
            'name_uz' => 'Aviа',
            'is_active' => true,
        ];
    }
}
