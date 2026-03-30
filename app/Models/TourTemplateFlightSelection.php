<?php

// File: app/Models/TourTemplateFlightSelection.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourTemplateFlightSelection extends Model
{
    protected $fillable = [
        'tour_template_leg_id',
        'flight_id',
        'provider_flight_id',
        'airline_code',
        'flight_number',
        'departure_datetime',
        'arrival_datetime',
        'price_cents',
        'currency',
        'seats_available',
        'raw_data',
        'selected_at',
    ];

    protected function casts(): array
    {
        return [
            'departure_datetime' => 'datetime',
            'arrival_datetime' => 'datetime',
            'price_cents' => 'integer',
            'seats_available' => 'integer',
            'raw_data' => 'array',
            'selected_at' => 'datetime',
        ];
    }

    public function leg(): BelongsTo
    {
        return $this->belongsTo(TourTemplateLeg::class, 'tour_template_leg_id');
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function priceFormatted(): string
    {
        return number_format($this->price_cents / 100, 2) . ' ' . $this->currency;
    }
}
