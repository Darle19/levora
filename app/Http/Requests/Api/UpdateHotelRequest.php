<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ru' => ['nullable', 'string', 'max:255'],
            'name_uz' => ['nullable', 'string', 'max:255'],
            'hotel_category_id' => ['sometimes', 'required', 'integer', 'exists:hotel_categories,id'],
            'resort_id' => ['nullable', 'integer', 'exists:resorts,id'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description_en' => ['nullable', 'string'],
            'description_ru' => ['nullable', 'string'],
            'description_uz' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'amenities' => ['nullable', 'array'],
            'rating' => ['nullable', 'numeric', 'between:0,10'],
            'price_per_person' => ['nullable', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'is_active' => ['boolean'],
        ];
    }
}
