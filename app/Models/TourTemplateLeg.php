<?php

namespace App\Models;

use App\Enums\TimeRange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TourTemplateLeg extends Model
{
    protected $fillable = [
        'tour_template_id',
        'leg_order',
        'departure_city_id',
        'arrival_city_id',
        'airline_id',
        'day_offset',
        'preferred_time_range',
        'passenger_count',
        'flight_source',
        'round_trip_pair_id',
    ];

    protected function casts(): array
    {
        return [
            'leg_order' => 'integer',
            'day_offset' => 'integer',
            'preferred_time_range' => TimeRange::class,
            'passenger_count' => 'integer',
        ];
    }

    // ── Relationships ──

    public function template(): BelongsTo
    {
        return $this->belongsTo(TourTemplate::class, 'tour_template_id');
    }

    public function departureCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'departure_city_id');
    }

    public function arrivalCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'arrival_city_id');
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    /**
     * Airlines allowed to serve this leg. A leg may be operated by any of
     * these; the FlightPath generator emits one path per valid airline combo.
     */
    public function airlines(): BelongsToMany
    {
        return $this->belongsToMany(Airline::class, 'tour_template_leg_airlines');
    }

    public function flightSelection(): HasOne
    {
        return $this->hasOne(TourTemplateFlightSelection::class);
    }

    public function roundTripPair(): BelongsTo
    {
        return $this->belongsTo(self::class, 'round_trip_pair_id');
    }

    // ── Helpers ──

    /**
     * Calculate the actual departure date given a base date.
     */
    public function departureDateFor(string $baseDate): string
    {
        return date('Y-m-d', strtotime($baseDate . " +{$this->day_offset} days"));
    }

    public function hasFlightSelected(): bool
    {
        return $this->flightSelection()->exists();
    }

    public function isRoundTrip(): bool
    {
        return $this->round_trip_pair_id !== null;
    }

    public function usesRapidApi(): bool
    {
        return $this->flight_source === 'rapidapi';
    }
}
