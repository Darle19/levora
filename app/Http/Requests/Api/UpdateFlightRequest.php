<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'airline_id' => ['sometimes', 'required', 'integer', 'exists:airlines,id'],
            'origin_city_id' => ['sometimes', 'required', 'integer', 'exists:cities,id', 'different:destination_city_id'],
            'destination_city_id' => ['sometimes', 'required', 'integer', 'exists:cities,id'],
            'from_airport_id' => ['sometimes', 'required', 'integer', 'exists:airports,id'],
            'to_airport_id' => ['sometimes', 'required', 'integer', 'exists:airports,id'],
            'currency_id' => ['sometimes', 'required', 'integer', 'exists:currencies,id'],
            'flight_number' => ['sometimes', 'required', 'string', 'max:20'],
            'departure_date' => ['sometimes', 'required', 'date'],
            'departure_time' => ['sometimes', 'required', 'date_format:H:i'],
            'arrival_date' => ['nullable', 'date'],
            'arrival_time' => ['sometimes', 'required', 'date_format:H:i'],
            'price_adult' => ['sometimes', 'required', 'numeric', 'min:0'],
            'price_child' => ['nullable', 'numeric', 'min:0'],
            'price_infant' => ['nullable', 'numeric', 'min:0'],
            'hard_block_price' => ['nullable', 'numeric', 'min:0'],
            'soft_block_price' => ['nullable', 'numeric', 'min:0'],
            'soft_block_release_days' => ['nullable', 'integer', 'min:0'],
            'available_seats' => ['sometimes', 'required', 'integer', 'min:0'],
            'class_type' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'origin_city_id.different' => 'Origin and destination cities must be different.',
        ];
    }
}
