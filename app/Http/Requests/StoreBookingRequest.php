<?php

namespace App\Http\Requests;

use App\Models\Tour;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        // Convert notes[] checkboxes array to a string
        if (is_array($this->notes)) {
            $combined = implode(', ', $this->notes);
            if ($this->notes_text) {
                $combined .= '. ' . $this->notes_text;
            }
            $this->merge(['notes' => $combined]);
        }
    }

    public function rules(): array
    {
        return [
            // Either tour_id or flight_path_id required
            'tour_id' => 'required_without:flight_path_id|nullable|exists:tours,id',
            'flight_path_id' => 'required_without:tour_id|nullable|exists:flight_paths,id',
            'hotel_ids' => 'nullable|string',

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
            'tourists.*.document_series' => 'nullable|string|max:20',
            'tourists.*.passport_number' => 'required|string|max:50',
            'tourists.*.passport_expiry' => 'required|date|after:today',
            'tourists.*.passport_issued' => 'nullable|date',
            'tourists.*.issued_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'notes_text' => 'nullable|string|max:1000',
            'services' => 'nullable|array',
            'services.*' => 'nullable',
            'insurances' => 'nullable|array',
            'insurances.*' => 'nullable',

            // Legacy Amadeus flight selections
            'amadeus_flights' => 'nullable|array',
            'amadeus_flights.*.segment_id' => 'required|exists:tour_amadeus_segments,id',
            'amadeus_flights.*.offer_id' => 'required|string|max:255',
            'amadeus_flights.*.airline' => 'required|string|max:10',
            'amadeus_flights.*.airline_name' => 'required|string|max:255',
            'amadeus_flights.*.flight_number' => 'required|string|max:20',
            'amadeus_flights.*.origin' => 'required|string|size:3',
            'amadeus_flights.*.destination' => 'required|string|size:3',
            'amadeus_flights.*.departure_date' => 'required|date',
            'amadeus_flights.*.departure_time' => 'required|string|max:5',
            'amadeus_flights.*.arrival_date' => 'required|date',
            'amadeus_flights.*.arrival_time' => 'required|string|max:5',
            'amadeus_flights.*.duration' => 'nullable|string|max:20',
            'amadeus_flights.*.stops' => 'nullable|integer|min:0',
            'amadeus_flights.*.cabin_class' => 'required|string|max:20',
            'amadeus_flights.*.price_per_adult' => 'required|numeric|min:0',
            'amadeus_flights.*.price_per_child' => 'nullable|numeric|min:0',
            'amadeus_flights.*.price_per_infant' => 'nullable|numeric|min:0',
            'amadeus_flights.*.currency' => 'required|string|size:3',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tourId = $this->input('tour_id');
            if (! $tourId) {
                return; // FlightPath booking, no Amadeus validation needed
            }

            $tour = Tour::with('amadeusSegments')->find($tourId);
            if (! $tour) {
                return;
            }

            $activeSegments = $tour->amadeusSegments->where('is_active', true);
            if ($activeSegments->isEmpty()) {
                return;
            }

            $selections = collect($this->input('amadeus_flights', []));
            $selectedSegmentIds = $selections->pluck('segment_id')->filter()->toArray();

            foreach ($activeSegments as $segment) {
                if (! in_array($segment->id, $selectedSegmentIds)) {
                    $validator->errors()->add(
                        'amadeus_flights',
                        "Please select a flight for segment {$segment->originAirport->code} → {$segment->destinationAirport->code}."
                    );
                }
            }
        });
    }
}
