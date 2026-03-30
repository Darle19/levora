<?php

// File: app/Http/Requests/Api/UpdateTourTemplateRequest.php

namespace App\Http\Requests\Api;

use App\Enums\TourTemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTourTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'route_name' => ['sometimes', 'required', 'string', 'max:255'],
            'departure_city_id' => ['sometimes', 'required', 'integer', 'exists:cities,id'],
            'base_currency' => ['sometimes', 'string', 'size:3'],
            'margin_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::enum(TourTemplateStatus::class)],

            'stays' => ['sometimes', 'array', 'min:1'],
            'stays.*.city_id' => ['required', 'integer', 'exists:cities,id'],
            'stays.*.nights' => ['required', 'integer', 'min:1'],
            'stays.*.check_in_date' => ['nullable', 'date'],
            'stays.*.check_out_date' => ['nullable', 'date', 'after_or_equal:stays.*.check_in_date'],

            'legs' => ['sometimes', 'array', 'min:1'],
            'legs.*.departure_city_id' => ['required', 'integer', 'exists:cities,id'],
            'legs.*.arrival_city_id' => ['required', 'integer', 'exists:cities,id', 'different:legs.*.departure_city_id'],
            'legs.*.departure_date' => ['required', 'date'],
            'legs.*.arrival_date' => ['nullable', 'date', 'after_or_equal:legs.*.departure_date'],
            'legs.*.preferred_time_range' => ['sometimes', 'string', 'in:any,morning,afternoon,evening'],
            'legs.*.passenger_count' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
