<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Tourist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingDocument>
 */
class BookingDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'type' => fake()->randomElement(['confirmation', 'memo', 'voucher', 'ticket', 'insurance']),
            'tourist_id' => null,
            'file_path' => 'documents/test/test_document.pdf',
            'metadata' => null,
        ];
    }

    public function forTourist(Tourist $tourist): static
    {
        return $this->state(fn () => ['tourist_id' => $tourist->id]);
    }

    public function confirmation(): static
    {
        return $this->state(fn () => ['type' => 'confirmation']);
    }

    public function voucher(): static
    {
        return $this->state(fn () => ['type' => 'voucher']);
    }

    public function ticket(): static
    {
        return $this->state(fn () => ['type' => 'ticket']);
    }

    public function insurance(): static
    {
        return $this->state(fn () => ['type' => 'insurance']);
    }
}
