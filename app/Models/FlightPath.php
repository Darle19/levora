<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlightPath extends Model
{
    protected $fillable = [
        'tour_template_id',
        'route_name',
        'departure_date',
        'departure_city_id',
        'total_price',
        'currency_id',
        'nights',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'total_price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function tourTemplate(): BelongsTo
    {
        return $this->belongsTo(TourTemplate::class);
    }

    public function departureCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'departure_city_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function legs(): HasMany
    {
        return $this->hasMany(FlightPathLeg::class)->orderBy('leg_order');
    }

    public function stays(): HasMany
    {
        return $this->hasMany(FlightPathStay::class)->orderBy('stay_order');
    }

    /**
     * Dynamic flight total — always calculated from current flight prices.
     */
    public function getFlightTotalAttribute(): float
    {
        if (! $this->relationLoaded('legs')) {
            $this->load('legs.flight');
        }

        return $this->legs->sum(fn ($leg) => (float) ($leg->flight?->price_adult ?? 0));
    }

    /**
     * Minimum available seats across all flight legs.
     */
    public function getMinSeatsAttribute(): int
    {
        if (! $this->relationLoaded('legs')) {
            $this->load('legs.flight');
        }

        return $this->legs->min(fn ($leg) => (int) ($leg->flight?->available_seats ?? 0)) ?? 0;
    }
}
