<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agency>
 */
class AgencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => fake()->company() . ' LLC',
            'legal_address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'director' => fake()->name(),
            'inn' => fake()->numerify('#########'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}
