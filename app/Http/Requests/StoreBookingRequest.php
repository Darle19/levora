<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tour_id' => 'required|exists:tours,id',
            'room_type_id' => [
                'nullable',
                Rule::exists('tour_prices', 'room_type_id')
                    ->where('tour_id', $this->input('tour_id'))
                    ->where('is_active', true),
            ],
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'tourists' => 'required|array|min:1',
            'tourists.*.title' => 'required|in:MR,MRS,CHD,INF',
            'tourists.*.gender' => 'required|in:male,female',
            'tourists.*.last_name' => 'required|string|max:255',
            'tourists.*.first_name' => 'required|string|max:255',
            'tourists.*.birth_date' => 'required|date|before:today',
            'tourists.*.birth_country' => 'nullable|string|max:100',
            'tourists.*.nationality' => 'required|string|max:100',
            'tourists.*.document_type' => 'nullable|string|max:50',
            'tourists.*.passport_series' => 'nullable|string|max:20',
            'tourists.*.passport_number' => 'required|string|max:50',
            'tourists.*.passport_expiry' => 'required|date|after:today',
            'tourists.*.passport_issued' => 'nullable|date|before:today',
            'tourists.*.passport_issued_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'special_requests' => 'nullable|array',
            'additional_services' => 'nullable|array',
            'additional_services.*' => 'exists:additional_services,id',
        ];
    }
}
