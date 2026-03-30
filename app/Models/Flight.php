<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flight extends Model
{
    use HasFactory;

    protected $fillable = [
        'airline_id',
        'origin_city_id',
        'destination_city_id',
        'from_airport_id',
        'to_airport_id',
        'currency_id',
        'flight_number',
        'departure_date',
        'departure_time',
        'arrival_date',
        'arrival_time',
        'price_adult',
        'price_child',
        'price_infant',
        'hard_block_price',
        'soft_block_price',
        'soft_block_release_days',
        'available_seats',
        'class_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'departure_date' => 'date',
            'arrival_date' => 'date',
            'price_adult' => 'decimal:2',
            'price_child' => 'decimal:2',
            'price_infant' => 'decimal:2',
            'hard_block_price' => 'decimal:2',
            'soft_block_price' => 'decimal:2',
            'soft_block_release_days' => 'integer',
            'available_seats' => 'integer',
        ];
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function fromAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'from_airport_id');
    }

    public function toAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'to_airport_id');
    }

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class, 'tour_flight')
            ->withPivot('direction');
    }

    public function flightPathLegs(): HasMany
    {
        return $this->hasMany(FlightPathLeg::class);
    }

    public function scopeFromCity($query, int $cityId)
    {
        return $query->where('origin_city_id', $cityId);
    }

    public function scopeToCity($query, int $cityId)
    {
        return $query->where('destination_city_id', $cityId);
    }

    public function scopeForCity($query, int $cityId)
    {
        return $query->where(function ($q) use ($cityId) {
            $q->where('origin_city_id', $cityId)
              ->orWhere('destination_city_id', $cityId);
        });
    }
}
