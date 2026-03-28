<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlightPath extends Model
{
    protected $fillable = [
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
     * Recalculate total_price from flight legs.
     */
    public function recalculatePrice(): void
    {
        $total = $this->legs()->with('flight')->get()->sum(fn ($leg) => (float) $leg->flight->price_adult);
        $this->updateQuietly(['total_price' => round($total, 2)]);
    }

    /**
     * Minimum available seats across all flight legs.
     */
    public function getMinSeatsAttribute(): int
    {
        return $this->legs()->with('flight')->get()->min(fn ($leg) => $leg->flight->available_seats) ?? 0;
    }
}
