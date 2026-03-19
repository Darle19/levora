<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourAmadeusSegment extends Model
{
    protected $fillable = [
        'tour_id',
        'leg_order',
        'origin_airport_id',
        'destination_airport_id',
        'offset_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'leg_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function originAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'origin_airport_id');
    }

    public function destinationAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'destination_airport_id');
    }

    public function bookingFlights(): HasMany
    {
        return $this->hasMany(BookingAmadeusFlight::class);
    }
}
