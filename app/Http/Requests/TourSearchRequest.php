<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TourSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_id' => 'nullable|exists:countries,id',
            'resort_ids' => 'nullable|array',
            'resort_ids.*' => 'exists:resorts,id',
            'hotel_ids' => 'nullable|array',
            'hotel_ids.*' => 'exists:hotels,id',
            'departure_city_id' => 'nullable|exists:cities,id',
            'tour_type_id' => 'nullable|exists:tour_types,id',
            'program_type_id' => 'nullable|exists:program_types,id',
            'transport_type_id' => 'nullable|exists:transport_types,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'nights_from' => 'nullable|integer|min:1',
            'nights_to' => 'nullable|integer|min:1',
            'adults' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'child_ages' => 'nullable|array',
            'child_ages.*' => 'integer|min:0|max:17',
            'price_from' => 'nullable|numeric|min:0',
            'price_to' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'meal_type_ids' => 'nullable|array',
            'meal_type_ids.*' => 'exists:meal_types,id',
            'hotel_category_ids' => 'nullable|array',
            'hotel_category_ids.*' => 'exists:hotel_categories,id',
            'is_hot' => 'nullable|boolean',
            'instant_confirmation' => 'nullable|boolean',
            'no_stop_sale' => 'nullable|boolean',
            'with_flight' => 'nullable|boolean',
            'direct_flight' => 'nullable|boolean',
            'sort_by' => 'nullable|in:price,date_from,nights,hotel_name',
            'sort_dir' => 'nullable|in:asc,desc',
            'group_by_hotel' => 'nullable|boolean',
        ];
    }
}
