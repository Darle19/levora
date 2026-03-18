<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAmadeusFlight extends Model
{
    protected $fillable = [
        'booking_id',
        'tour_amadeus_segment_id',
        'amadeus_offer_id',
        'airline',
        'airline_name',
        'flight_number',
        'origin',
        'destination',
        'departure_date',
        'departure_time',
        'arrival_date',
        'arrival_time',
        'duration',
        'stops',
        'cabin_class',
        'price_per_adult',
        'price_per_child',
        'price_per_infant',
        'price_total',
        'currency',
        'raw_offer_data',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'arrival_date' => 'date',
            'stops' => 'integer',
            'price_per_adult' => 'decimal:2',
            'price_per_child' => 'decimal:2',
            'price_per_infant' => 'decimal:2',
            'price_total' => 'decimal:2',
            'raw_offer_data' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function tourAmadeusSegment(): BelongsTo
    {
        return $this->belongsTo(TourAmadeusSegment::class);
    }
}
